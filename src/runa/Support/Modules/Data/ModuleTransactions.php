<?php

namespace Runa\Support\Modules\Data;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\DatabaseManager;
use ReflectionClass;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\GraphBlocker;
use Runa\Contracts\Modules\MutationDefinition;
use Runa\Contracts\Modules\OperationContext;
use Runa\Contracts\Modules\TransactionalMutation;
use Runa\Contracts\Modules\TransactionSecurity;
use Runa\ModuleFramework\Api\BoundaryValues;
use Runa\ModuleFramework\Api\ModuleDeclarations;
use Runa\ModuleFramework\Api\OperationGuard;
use Runa\ModuleFramework\Api\PrincipalScope;
use Runa\ModuleFramework\Data\AuthorityLock;
use Runa\ModuleFramework\ModuleDiscovery;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\ModuleFramework\Transactions\HealthAdmissions;
use Runa\Support\Modules\Api\OperationRejected;
use Throwable;

/** Source-owned phases shared by standalone owners and aggregate coordinators. */
final readonly class ModuleTransactions
{
    public function __construct(private DatabaseManager $database, private ModuleRegistry $registry,
        private ModuleDeclarations $declarations, private OperationGuard $guard, private PrincipalScope $principals,
        private AuthorityLock $authority, private TransactionSecurity $security,
        private HealthAdmissions $admissions, private Application $app) {}

    /** @param list<TransactionalMutation> $mutations
     * @return array<string,array<string,mixed>>
     */
    public function run(array $mutations): array
    {
        $connection = $this->database->connection();
        if ($connection->getDriverName() !== 'pgsql' || $connection->transactionLevel() !== 0 || ! array_is_list($mutations) || $mutations === [] || count($mutations) > 16) {
            throw new OperationRejected('transaction_boundary_invalid', 'runa', 'transaction', 409);
        }
        $items = $resources = $proofs = $subjects = [];
        foreach ($mutations as $mutation) {
            if (! $mutation instanceof TransactionalMutation) {
                throw new \InvalidArgumentException('Invalid owning mutation.');
            }
            $definition = $mutation->definition();
            $this->owned($mutation, $definition);
            $id = $definition->owner.'.'.$definition->key;
            if (isset($items[$id])) {
                throw new OperationRejected('transaction_duplicate_mutation', 'runa', 'transaction', 409);
            }
            $items[$id] = [$mutation, $definition];
            foreach ($definition->resources as $resource) {
                if (isset($resources[$resource])) {
                    throw new OperationRejected('transaction_duplicate_writer', 'runa', 'transaction', 409);
                }
                $resources[$resource] = true;
            }
            array_push($subjects, ...$definition->subjects);
            foreach ($definition->proofs as $proof) {
                if (isset($proofs[$proof->action]) && ($proof->everyAction || $proofs[$proof->action]->everyAction)) {
                    throw new OperationRejected('transaction_duplicate_proof', 'runa', 'transaction', 409);
                }
                // Do not merge even reusable demands with different strengths/TTLs.
                if (isset($proofs[$proof->action]) && $proofs[$proof->action] != $proof) {
                    throw new OperationRejected('transaction_proof_conflict', 'runa', 'transaction', 409);
                }
                $proofs[$proof->action] = $proof;
            }
        }
        $principal = $this->principals->current();
        if ($principal !== null && $principal->kind === 'human') {
            $subjects[] = $principal->publicId;
        }
        $subjects = array_values(array_unique($subjects));
        sort($subjects);
        ksort($items);
        ksort($proofs);
        if (count($subjects) > 100) {
            throw new OperationRejected('transaction_subject_limit', 'runa', 'transaction', 422);
        }
        $started = true;
        try {
            return $this->admissions->run(function () use ($items, $proofs): void {
                foreach ($items as [$mutation, $definition]) {
                    $this->admit($definition);
                    $mutation->preflight();
                }
                if ($proofs !== []) {
                    $this->guard->enter('runa.auth', new ApiOperation('step_up.assert'));
                }
                // Owning Audit is mandatory for mutations; proof consumption records here too.
                $this->guard->enter('runa.audit', new ApiOperation('audit.record'));
            }, function () use ($connection, $items, $proofs, $subjects, $principal, &$started): array {
                $generation = $this->registry->snapshot()->generation;

                return $connection->transaction(function () use ($connection, $items, $proofs, $subjects, $principal, $generation, &$started): array {
                    $started = true;
                    $connection->table('shared.module_registry_state')->where('id', 1)->lockForUpdate()->first()
                        ?? throw new OperationRejected('module_unavailable', 'runa', 'transaction');
                    if ($this->registry->snapshot()->generation !== $generation || $this->principals->current() !== $principal) {
                        throw new OperationRejected('availability_changed', 'runa', 'transaction', 409);
                    }
                    $this->authority->acquire($connection);
                    $contexts = [];
                    foreach ($items as [, $definition]) {
                        array_push($contexts, ...$this->admit($definition));
                    }
                    $this->security->lockSubjects($subjects);
                    foreach ($items as [$mutation]) {
                        $mutation->lock();
                    }
                    foreach ($items as [$mutation]) {
                        $mutation->validate();
                    }
                    foreach ($proofs as $proof) {
                        $this->security->proof($proof, false);
                    }
                    foreach ($proofs as $proof) {
                        $this->security->proof($proof, true);
                    }
                    foreach ($items as [$mutation, $definition]) {
                        $this->admit($definition);
                        $mutation->apply();
                    }
                    $results = [];
                    foreach ($items as $id => [$mutation, $definition]) {
                        $results[$id] = $mutation->result();
                    }
                    if ($this->registry->snapshot()->generation !== $generation || $connection->transactionLevel() !== 1) {
                        throw new OperationRejected('availability_changed', 'runa', 'transaction', 409);
                    }
                    foreach ($contexts as $context) {
                        $this->guard->assertAvailable($context);
                    }
                    $this->admissions->assertCurrent();
                    BoundaryValues::check($results, "\0");
                    if (strlen(json_encode($results, JSON_THROW_ON_ERROR)) > 1048576) {
                        throw new OperationRejected('transaction_result_limit', 'runa', 'transaction');
                    }

                    return $results;
                }, 1);
            });
        } catch (Throwable $error) {
            if ($started && $connection->transactionLevel() === 0) {
                $reason = $error instanceof OperationRejected && preg_match('/\A[a-z][a-z0-9_]{0,63}\z/D', $error->reason) === 1 ? $error->reason : 'transaction_failed';
                $refusalFailed = false;
                foreach ($items as [$mutation]) {
                    try {
                        $mutation->refused($reason);
                    } catch (Throwable) {
                        $refusalFailed = true;
                    }
                }
                if ($refusalFailed) {
                    throw new OperationRejected('transaction_refusal_unavailable', 'runa', 'transaction', reasons: [new GraphBlocker($reason, [])]);
                }
            }
            throw $error;
        }
    }

    /** @return list<OperationContext> */
    private function admit(MutationDefinition $definition): array
    {
        $contexts = [];
        foreach ($definition->access as $access) {
            $contexts[] = $this->guard->enter($access->module, $access->operation);
        }

        return $contexts;
    }

    private function owned(TransactionalMutation $mutation, MutationDefinition $definition): void
    {
        if ($definition->owner === 'app') {
            $reflection = new ReflectionClass($mutation);
            $file = $reflection->getFileName();
            if (! str_starts_with($reflection->name, 'App\\') || str_starts_with($reflection->name, 'App\\Modules\\') || $file === false
                || ! str_starts_with(ModuleDiscovery::ownedPath($this->app->basePath(), $file), $this->app->basePath('app').'/')) {
                throw new \InvalidArgumentException('Mutation escaped application source.');
            }
        } else {
            $record = $this->registry->snapshot()->modules[$definition->owner] ?? throw new \InvalidArgumentException('Mutation owner unavailable.');
            $this->declarations->inspect($record, $mutation::class);
        }
    }
}
