<?php

namespace Runa\Modules\Permissions\Support;

use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\Modules\Auth\Contracts\OperatorAuthority as OperatorContract;
use Runa\Support\Modules\Data\AuthorityLock;

/** Only the trusted framework operator bridge calls this participant, never public ingress. */
final readonly class OperatorAuthority implements OperatorContract
{
    public function __construct(private DatabaseManager $database, private AuthorityLock $lock, private AccessTransaction $audit) {}

    public function initialize(Connection $connection, string $publicId): void
    {
        $role = $this->role($connection, $publicId);
        // The Auth singleton owns first-recipient idempotency; never adopt an existing authority set.
        if ($connection->table('runa_permissions.user_roles')->where('role_id', $role->id)->exists()) {
            throw new \RuntimeException('Administrator authority is already initialized.');
        }
        $connection->table('runa_permissions.user_roles')->insert(['role_id' => $role->id, 'user_public_id' => $publicId,
            'created_at' => now('UTC'), 'updated_at' => now('UTC')]);
        $revision = (int) ($connection->table('runa_permissions.user_access')->where('user_public_id', $publicId)->value('version') ?? 0);
        $connection->table('runa_permissions.user_access')->updateOrInsert(['user_public_id' => $publicId], ['version' => $revision + 1, 'updated_at' => now('UTC')]);
        $this->audit->record('authority.initialized', 'user', $publicId, ['super_admin' => false, 'revision' => $revision],
            ['super_admin' => true, 'revision' => $revision + 1], ['role' => $role->public_id]);
    }

    public function isAdministrator(Connection $connection, string $publicId): bool
    {
        $role = $this->role($connection, $publicId);

        return $connection->table('runa_permissions.user_roles')->where('role_id', $role->id)->where('user_public_id', $publicId)->exists();
    }

    private function role(Connection $connection, string $publicId): object
    {
        new TrustedPrincipal($publicId);
        if ($connection !== $this->database->connection()) {
            throw new \RuntimeException('Operator authority requires the default transaction connection.');
        }
        $this->lock->acquire($connection);

        return $connection->table('runa_permissions.roles')->where('code', 'runa.permissions.super_admin')
            ->where('module_id', 'runa.permissions')->where('system', true)->whereNull('deleted_at')->lockForUpdate()->first()
            ?? throw new \RuntimeException('Protected system role unavailable.');
    }
}
