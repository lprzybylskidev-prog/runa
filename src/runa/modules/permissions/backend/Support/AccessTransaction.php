<?php

namespace Runa\Modules\Permissions\Support;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\QueryException;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Modules\Audit\Contracts\AuditEntity;
use Runa\Modules\Audit\Contracts\AuditEvent;
use Runa\Modules\Audit\Contracts\AuditRecords;
use Runa\Modules\Auth\Contracts\AuthStepUp;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationGuard;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Data\AuthorityLock;

final readonly class AccessTransaction
{
    public function __construct(private DatabaseManager $database, private AuthorityLock $authority, private ModuleApis $apis, private OperationGuard $guard) {}

    public function run(Closure $work, bool $write = false): mixed
    {
        // Savepoint also rolls back writes/proof consumption when an outer caller catches a refusal.
        $root = $this->database->connection()->transactionLevel() === 0;
        try {
            return $this->database->connection()->transaction(function (Connection $connection) use ($work, $write): mixed {
                // Exclusive before authority: Auth step-up/outbox uses the same registry row.
                $connection->table('shared.module_registry_state')->where('id', 1)->lockForUpdate()->first()
                    ?? throw new \RuntimeException('Module registry unavailable.');
                $this->authority->acquire($connection);
                $this->guard->enter('runa.permissions', new ApiOperation('access.manage', 'runa.permissions.access.manage'));
                if ($write) {
                    $this->apis->resolve('runa.auth', AuthStepUp::class, '1.0.0')->assertVerified('runa.permissions.access.manage', 'both', everyAction: true);
                }

                return $work($connection);
            });
        } catch (QueryException $error) {
            if ($error->getCode() !== '23505') {
                throw $error;
            }
            $refusal = new OperationRejected('access_definition_conflict', 'runa.permissions', 'access', 409);
        } catch (OperationRejected $error) {
            $refusal = $error;
        }
        if ($root) {
            $this->apis->resolve('runa.audit', AuditRecords::class, '1.0.0')->attempt(new AuditEvent('runa.permissions', 'access.refused', 'refused', context: ['reason' => $refusal->reason]));
        }
        // Nested consumers must roll back their root before recording independent refusal history.
        throw $refusal;
    }

    public function record(string $action, string $kind, string $publicId, ?array $before, ?array $after, array $context = []): void
    {
        $this->apis->resolve('runa.audit', AuditRecords::class, '1.0.0')->record(new AuditEvent('runa.permissions', $action,
            entities: [new AuditEntity($kind === 'user' ? 'runa.auth' : 'runa.permissions', $kind, $publicId)],
            changes: ['before' => $before, 'after' => $after], context: $context));
    }
}
