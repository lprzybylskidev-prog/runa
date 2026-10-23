<?php

namespace Runa\Modules\Auth\Support;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Runa\Modules\Audit\Contracts\AuditEntity;
use Runa\Modules\Audit\Contracts\AuditEvent;
use Runa\Modules\Audit\Contracts\AuditRecords;
use Runa\Modules\Auth\Models\User;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Data\AuthorityLock;

/** Registry -> settings -> authority -> Auth user -> token; one default connection. */
final readonly class EntryTransaction
{
    public function __construct(private DatabaseManager $database, private AuthorityLock $authority, private ModuleApis $apis) {}

    public function run(Closure $work): mixed
    {
        return $this->database->connection()->transaction(function (Connection $connection) use ($work): mixed {
            // Exclusive up front: durable ModuleWork enqueue shares this registry lock.
            $connection->table('shared.module_registry_state')->where('id', 1)->lockForUpdate()->first()
                ?? throw new \RuntimeException('Module registry unavailable.');

            return $work($connection);
        });
    }

    public function lockAuthority(Connection $connection): void
    {
        $this->authority->acquire($connection);
    }

    public function loginRefused(): void
    {
        $this->apis->resolve('runa.audit', AuditRecords::class, '1.0.0')->attempt(new AuditEvent('runa.auth', 'login.refused', 'refused'));
    }

    public function record(string $action, User $user): void
    {
        $this->apis->resolve('runa.audit', AuditRecords::class, '1.0.0')->record(new AuditEvent('runa.auth', $action,
            entities: [new AuditEntity('runa.auth', 'user', $user->public_id)]));
    }
}
