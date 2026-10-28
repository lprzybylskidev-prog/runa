<?php

namespace Runa\Modules\Permissions\Support;

use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Runa\Contracts\Modules\AuthorityGuard;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\ModuleFramework\Data\AuthorityLock;
use Runa\Modules\Auth\Contracts\AuthorizationIdentity;
use Runa\Support\Modules\Api\OperationRejected;

/** Trusted invariant participant uses Auth's public non-recursive eligibility contract. */
final readonly class PreserveAuthority implements AuthorityGuard
{
    public function __construct(private DatabaseManager $database, private AuthorityLock $lock, private AuthorizationIdentity $identities) {}

    public function assertMayRemove(Connection $connection, array $publicIds): void
    {
        if ($connection !== $this->database->connection()) {
            throw new \RuntimeException('Authority requires the default transaction connection.');
        }
        $this->lock->acquire($connection);
        foreach ($publicIds as $id) {
            new TrustedPrincipal($id);
        }
        if ($publicIds === []) {
            return; // Serialized creation/activation/profile work does not remove membership.
        }
        $members = $connection->table('runa_permissions.user_roles as ur')
            ->join('runa_permissions.roles as r', 'r.id', '=', 'ur.role_id')
            ->where('r.code', 'runa.permissions.super_admin')->where('r.system', true)->whereNull('r.deleted_at');
        if (! (clone $members)->whereIn('ur.user_public_id', $publicIds)->exists()) {
            return;
        }
        foreach ($members->whereNotIn('ur.user_public_id', $publicIds)->orderBy('ur.user_public_id')->select('ur.user_public_id')->lazy(100) as $member) {
            if ($this->identities->eligibility($member->user_public_id)?->mayOperate() === true) {
                return;
            }
        }
        throw new OperationRejected('last_superadmin_required', 'runa.permissions', 'authority.guard', 409);
    }
}
