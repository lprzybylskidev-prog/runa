<?php

namespace Runa\Modules\Permissions\Support;

use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\ModuleFramework\Data\AuthorityLock;
use Runa\Modules\Auth\Contracts\OperatorAuthority as OperatorContract;

/** Trusted framework recovery/bulk-membership participant, never direct public ingress. */
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
        /** @var int|null $storedRevision */
        $storedRevision = $connection->table('runa_permissions.user_access')->where('user_public_id', $publicId)->value('version');
        $revision = $storedRevision ?? 0;
        $connection->table('runa_permissions.user_access')->updateOrInsert(['user_public_id' => $publicId], ['version' => $revision + 1, 'updated_at' => now('UTC')]);
        $this->audit->record('authority.initialized', 'user', $publicId, ['super_admin' => false, 'revision' => $revision],
            ['super_admin' => true, 'revision' => $revision + 1], ['role' => $role->public_id]);
    }

    public function isAdministrator(Connection $connection, string $publicId): bool
    {
        $role = $this->role($connection, $publicId);

        return $connection->table('runa_permissions.user_roles')->where('role_id', $role->id)->where('user_public_id', $publicId)->exists();
    }

    public function administrators(Connection $connection, array $publicIds): array
    {
        if ($publicIds === [] || count($publicIds) > 100 || count(array_unique($publicIds)) !== count($publicIds)) {
            throw new \InvalidArgumentException('Expected one to 100 distinct selected identities.');
        }
        foreach ($publicIds as $publicId) {
            new TrustedPrincipal($publicId);
        }
        $role = $this->role($connection, $publicIds[0]);

        return $connection->table('runa_permissions.user_roles')->where('role_id', $role->id)
            ->whereIn('user_public_id', $publicIds)->orderBy('user_public_id')->pluck('user_public_id')->all();
    }

    /** @return \stdClass&object{id: int, public_id: string} */
    private function role(Connection $connection, string $publicId): object
    {
        new TrustedPrincipal($publicId);
        if ($connection !== $this->database->connection()) {
            throw new \RuntimeException('Operator authority requires the default transaction connection.');
        }
        $this->lock->acquire($connection);

        /** @var \stdClass&object{id: int, public_id: string} $role */
        $role = $connection->table('runa_permissions.roles')->where('code', 'runa.permissions.super_admin')
            ->where('module_id', 'runa.permissions')->where('system', true)->whereNull('deleted_at')->lockForUpdate()->first()
            ?? throw new \RuntimeException('Protected system role unavailable.');

        return $role;
    }
}
