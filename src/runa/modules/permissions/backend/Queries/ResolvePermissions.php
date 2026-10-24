<?php

namespace Runa\Modules\Permissions\Queries;

use Illuminate\Database\DatabaseManager;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\Modules\Auth\Contracts\AuthorizationIdentity;
use Runa\Modules\Permissions\Contracts\PermissionPage;
use Runa\Modules\Permissions\Contracts\Permissions;
use Runa\Support\Modules\Api\PrincipalScope;
use Runa\Support\Modules\ModuleAvailability;

/** Deliberately uncached: all workers and reused scopes read committed current assignments. */
final readonly class ResolvePermissions implements Permissions
{
    public function __construct(private DatabaseManager $database, private PrincipalScope $principals, private AuthorizationIdentity $identities, private ModuleAvailability $availability) {}

    public function allows(string $permission): bool
    {
        new ApiOperation($permission);
        $principal = $this->principals->current();
        if ($principal === null || $principal->kind !== 'human' || ! $this->ready()) {
            return false;
        }
        if (! ($this->identities->eligibility($principal->publicId)?->mayOperate() ?? false)) {
            return false;
        }
        $row = $this->database->connection()->table('runa_permissions.permissions')->where('code', $permission)->whereNull('deleted_at')->first();
        if ($row === null || ! $this->moduleAvailable($row->module_id)) {
            return false;
        }

        return $this->superAdmin($principal->publicId) || $this->direct($principal->publicId, $row->id) || $this->roles($principal->publicId, $row->id) !== [];
    }

    public function explain(string $userPublicId, ?string $after = null): PermissionPage
    {
        new TrustedPrincipal($userPublicId);
        if ($after !== null) {
            new ApiOperation($after);
        }
        if (! $this->ready()) {
            throw new \RuntimeException('Permissions unavailable.');
        }
        $eligible = $this->identities->eligibility($userPublicId)?->mayOperate() ?? false;
        $superAdmin = $this->superAdmin($userPublicId);
        $rows = $this->database->connection()->table('runa_permissions.permissions')->whereNull('deleted_at')
            ->when($after !== null, fn ($query) => $query->where('code', '>', $after))->orderBy('code')->limit(101)->get();
        $more = $rows->count() > 100;
        $page = $rows->take(100);
        $ids = $page->pluck('id')->all();
        $connection = $this->database->connection();
        $directIds = $connection->table('runa_permissions.user_permissions')->where('user_public_id', $userPublicId)
            ->whereIn('permission_id', $ids)->pluck('permission_id')->all();
        $ranked = $connection->table('runa_permissions.user_roles as ur')
            ->join('runa_permissions.roles as r', 'r.id', '=', 'ur.role_id')
            ->join('runa_permissions.role_permissions as rp', 'rp.role_id', '=', 'r.id')
            ->where('ur.user_public_id', $userPublicId)->whereIn('rp.permission_id', $ids)->whereNull('r.deleted_at')
            ->select(['rp.permission_id', 'r.public_id'])->selectRaw('row_number() over (partition by rp.permission_id order by r.public_id) as position');
        $roleRows = $connection->query()->fromSub($ranked, 'grants')->where('position', '<=', 33)->orderBy('position')->get()->groupBy('permission_id');
        $modules = $page->pluck('module_id')->filter()->unique()->values()->all();
        $statuses = $this->availability->statuses($modules, 'permissions.resolve');
        $items = [];
        foreach ($page as $row) {
            $direct = in_array($row->id, $directIds, true);
            $roles = ($roleRows->get($row->id) ?? collect())->pluck('public_id')->all();
            $available = $row->module_id === null || $statuses[$row->module_id]->available;
            $items[] = ['code' => $row->code, 'direct' => $direct, 'roles' => array_slice($roles, 0, 32), 'roles_truncated' => count($roles) > 32,
                'available' => $available, 'effective' => $eligible && $available && ($superAdmin || $direct || $roles !== [])];
        }

        return new PermissionPage($userPublicId, $eligible, $superAdmin, $items, $more ? $items[array_key_last($items)]['code'] : null);
    }

    private function ready(): bool
    {
        return $this->availability->statuses('runa.permissions', 'permissions.resolve')['runa.permissions']->available;
    }

    private function moduleAvailable(?string $module): bool
    {
        return $module === null || $this->availability->statuses($module, 'permissions.resolve')[$module]->available;
    }

    private function superAdmin(string $user): bool
    {
        return $this->database->connection()->table('runa_permissions.user_roles as ur')
            ->join('runa_permissions.roles as r', 'r.id', '=', 'ur.role_id')->where('ur.user_public_id', $user)
            ->where('r.code', 'runa.permissions.super_admin')->where('r.system', true)->whereNull('r.deleted_at')->exists();
    }

    private function direct(string $user, int $permission): bool
    {
        return $this->database->connection()->table('runa_permissions.user_permissions')
            ->where('user_public_id', $user)->where('permission_id', $permission)->exists();
    }

    /** @return list<string> */
    private function roles(string $user, int $permission): array
    {
        return $this->database->connection()->table('runa_permissions.user_roles as ur')
            ->join('runa_permissions.roles as r', 'r.id', '=', 'ur.role_id')
            ->join('runa_permissions.role_permissions as rp', 'rp.role_id', '=', 'r.id')
            ->where('ur.user_public_id', $user)->where('rp.permission_id', $permission)->whereNull('r.deleted_at')
            ->orderBy('r.public_id')->limit(33)->pluck('r.public_id')->all();
    }
}
