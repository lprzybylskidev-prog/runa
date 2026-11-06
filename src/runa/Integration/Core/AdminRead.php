<?php

namespace Runa\Integration\Core;

use Closure;
use Illuminate\Database\DatabaseManager;
use Runa\ModuleFramework\Api\ApiDefinitions;
use Runa\ModuleFramework\Api\PrincipalScope;
use Runa\ModuleFramework\Management\ReadHandlerPlan;
use Runa\ModuleFramework\ModuleAvailability;
use Runa\ModuleFramework\ModuleHealth;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\ModuleFramework\Transactions\HealthAdmissions;
use Runa\Support\Modules\Api\OperationRejected;
use Throwable;

/** Named trusted read integration: source planning, health preflight, existing locked entry. */
final readonly class AdminRead
{
    public function __construct(private AdminEntry $entry, private DatabaseManager $database,
        private ModuleRegistry $registry, private PrincipalScope $principals, private ApiDefinitions $definitions,
        private ModuleAvailability $availability, private HealthAdmissions $admissions, private ModuleHealth $health) {}

    /** @template T
     * @param Closure():array<string,ReadHandlerPlan> $select
     * @param Closure(Closure(string,Closure):mixed,list<ReadHandlerPlan>):T $read
     * @return T */
    public function run(Closure $select, Closure $read): mixed
    {
        $connection = $this->database->connection();
        if ($connection->transactionLevel() !== 0) {
            throw new OperationRejected('admin_read_nested', 'runa.admin', 'section.read', 409);
        }
        $selection = $this->entry->read(function () use ($select, $read, $connection): array|CompletedAdminRead {
            $plans = $select();
            if (array_filter($plans, static fn (ReadHandlerPlan $plan): bool => $plan->calls !== [] || $plan->diagnostic !== null) === []) {
                // Keep ordinary reads in their original single entry transaction.
                $invoke = fn (string $id, Closure $handler): mixed => $this->admissions->read([], $connection, $handler);

                return new CompletedAdminRead($read($invoke, []));
            }

            return [$plans, $this->state(), $this->principals->current(), $connection->getPdo()];
        });
        if ($selection instanceof CompletedAdminRead) {
            return $selection->result;
        }
        [$plans, $state, $principal, $pdo] = $selection;
        $prepared = $failures = [];
        foreach ($plans as $id => $plan) {
            try {
                if ($plan->diagnostic !== null) {
                    throw new OperationRejected($plan->diagnostic, 'runa.admin', 'widget');
                }
                $prepared[$id] = [];
                foreach ($plan->calls as $call) {
                    [, $definition] = $this->definitions->get($call->module, $call->contract, $call->version);
                    $operation = $definition->operations[$call->method] ?? throw new OperationRejected('operation_not_declared', $call->module, 'read', 404);
                    $identity = [$call->module, $call->contract, $call->version, $call->method];
                    $prepared[$id][] = $this->admissions->prepareRead($identity, function () use ($call, $operation): void {
                        $status = $this->availability->statuses($call->module, $operation->key)[$call->module];
                        if (! $status->available) {
                            throw new OperationRejected('module_unavailable', $call->module, $operation->key, reasons: $status->reasons);
                        }
                    }, hash('sha256', serialize($definition)));
                }
            } catch (Throwable $error) {
                if ($plan->widget === null) {
                    throw $error instanceof OperationRejected ? $error : new OperationRejected('admin_read_unavailable', 'runa.admin', 'section.read');
                }
                $failures[$id] = $plan;
                unset($prepared[$id]);
            }
        }
        $generation = $this->registry->snapshot()->generation;
        if ($this->state() !== $state || $connection->getPdo() !== $pdo || $this->principals->current() !== $principal) {
            throw new OperationRejected('availability_changed', 'runa.admin', 'section.read', 409);
        }

        return $this->entry->read(function () use ($select, $read, $plans, $prepared, $failures, $generation, $state, $principal, $pdo, $connection): mixed {
            if ($this->registry->snapshot()->generation !== $generation || $this->state() !== $state || $connection->getPdo() !== $pdo || $this->principals->current() !== $principal) {
                throw new OperationRejected('availability_changed', 'runa.admin', 'section.read', 409);
            }
            $current = $select();
            foreach ($current as $id => $plan) {
                if (! isset($plans[$id]) || ! hash_equals($plans[$id]->fingerprint, $plan->fingerprint)) {
                    throw new OperationRejected('admin_read_plan_changed', 'runa.admin', 'section.read', 409);
                }
            }
            foreach ($plans as $id => $plan) {
                if (! isset($current[$id]) && ! isset($failures[$id])) {
                    throw new OperationRejected('admin_read_plan_changed', 'runa.admin', 'section.read', 409);
                }
            }
            $invoke = function (string $id, Closure $handler) use ($prepared, $failures, $connection): mixed {
                if (isset($failures[$id]) || ! array_key_exists($id, $prepared)) {
                    throw new OperationRejected('admin_widget_unavailable', 'runa.admin', 'widget');
                }

                return $this->admissions->read($prepared[$id], $connection, $handler);
            };
            $result = $read($invoke, array_values($failures));
            if ($this->registry->snapshot()->generation !== $generation || $this->state() !== $state || $connection->getPdo() !== $pdo || $this->principals->current() !== $principal) {
                throw new OperationRejected('availability_changed', 'runa.admin', 'section.read', 409);
            }

            foreach ($prepared as $calls) {
                foreach ($calls as $call) {
                    if (! $call->claimed) {
                        continue;
                    }
                    [, $definition] = $this->definitions->get($call->identity[0], $call->identity[1], $call->identity[2]);
                    if ($call->definitionFingerprint !== hash('sha256', serialize($definition))) {
                        throw new OperationRejected('admin_read_plan_changed', 'runa.admin', 'section.read', 409);
                    }
                    foreach ($call->evidence as $key => $evidence) {
                        $module = explode(':', $key, 2)[0];
                        $record = $this->registry->snapshot()->modules[$module] ?? null;
                        if ($record === null || $record->manifestHash !== $evidence['source'] || $this->health->fingerprint($record) !== $evidence['policy'] || $evidence['expiry'] <= now()->getTimestamp()) {
                            throw new OperationRejected('admission_expired', 'runa.admin', 'section.read', 409);
                        }
                    }
                }
            }

            return $result;
        });
    }

    /** Semantic source/flag/fence identity, excluding expected health publication. */
    private function state(): string
    {
        $snapshot = $this->registry->snapshot();
        $modules = [];
        foreach ($snapshot->modules as $id => $record) {
            $values = get_object_vars($record);
            unset($values['healthObservations']);
            $modules[$id] = $values;
        }

        return hash('sha256', serialize([$modules, $snapshot->fenced]));
    }
}
