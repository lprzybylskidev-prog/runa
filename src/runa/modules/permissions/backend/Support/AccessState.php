<?php

namespace Runa\Modules\Permissions\Support;

use Illuminate\Database\DatabaseManager;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\Modules\Auth\Contracts\AuthIdentities;
use Runa\Modules\Permissions\Contracts\AssignmentSet;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\ModuleAvailability;

/** Owned, bounded projections shared by assignment and definition operations. No grant cache. */
final readonly class AccessState
{
    public function __construct(private DatabaseManager $database, private ModuleApis $apis, private ModuleAvailability $availability) {}

    public function table(string $kind): string
    {
        return match ($kind) {
            'role' => 'roles', 'preset' => 'presets', 'permission' => 'permissions',
            default => throw new \InvalidArgumentException('Unknown access definition kind.'),
        };
    }

    public function user(string $publicId): array
    {
        new TrustedPrincipal($publicId);
        $eligibility = $this->apis->resolve('runa.auth', AuthIdentities::class, '1.0.0')->eligibility($publicId);
        $identity = $eligibility?->identity;
        if ($identity === null || $identity->deleted) {
            $this->reject('access_subject_missing', 404);
        }
        $c = $this->database->connection();
        $roles = $c->table('runa_permissions.user_roles as ur')->join('runa_permissions.roles as r', 'r.id', '=', 'ur.role_id')
            ->where('ur.user_public_id', $publicId)->whereNull('r.deleted_at')->orderBy('r.public_id')->limit(102)->get(['r.public_id', 'r.system']);
        $direct = $c->table('runa_permissions.user_permissions as up')->join('runa_permissions.permissions as p', 'p.id', '=', 'up.permission_id')
            ->where('up.user_public_id', $publicId)->whereNull('p.deleted_at')->orderBy('p.public_id')->limit(101)->pluck('p.public_id')->all();
        $set = new AssignmentSet($roles->where('system', false)->pluck('public_id')->all(), $direct);

        return ['user_public_id' => $publicId, 'identity_version' => $identity->version,
            'eligible' => $eligibility->mayOperate(), 'security_version' => $eligibility->securityVersion,
            'revision' => (int) ($c->table('runa_permissions.user_access')->where('user_public_id', $publicId)->value('version') ?? 0),
            'super_admin' => $roles->contains('system', true), 'assigned' => $set->toArray(), 'definitions' => $this->references($set)];
    }

    /** Validate references, including retained disabled-module definitions, without flattening roles. */
    public function references(AssignmentSet $set): array
    {
        $c = $this->database->connection();
        $members = $set->roles === [] ? collect() : $c->table('runa_permissions.role_permissions as rp')
            ->join('runa_permissions.roles as r', 'r.id', '=', 'rp.role_id')
            ->join('runa_permissions.permissions as p', 'p.id', '=', 'rp.permission_id')
            ->whereIn('r.public_id', $set->roles)->whereNull('p.deleted_at')->orderBy('r.public_id')->orderBy('p.public_id')
            ->limit(10001)->get(['r.public_id as role_public_id', 'p.public_id', 'p.code', 'p.version', 'p.module_id']);
        if ($members->count() > 10000) {
            $this->reject('access_reference_limit');
        }
        $grouped = $members->groupBy('role_public_id');
        $result = [];
        foreach (['roles' => $set->roles, 'permissions' => $set->permissions] as $kind => $ids) {
            $rows = $c->table('runa_permissions.'.$kind)->whereIn('public_id', $ids)->whereNull('deleted_at')->orderBy('public_id')->get();
            if ($rows->count() !== count($ids) || $rows->contains('system', true)) {
                $this->reject('access_reference_protected_or_missing');
            }
            foreach ($rows as $row) {
                $entry = ['public_id' => $row->public_id, 'code' => $row->code, 'version' => (int) $row->version, 'module' => $row->module_id];
                if ($kind === 'roles') {
                    $entry['permissions'] = ($grouped[$row->public_id] ?? collect())->map(fn ($p) => ['public_id' => $p->public_id, 'code' => $p->code, 'version' => (int) $p->version, 'module_id' => $p->module_id])->all();
                    if (count($entry['permissions']) > 100) {
                        $this->reject('access_reference_limit');
                    }
                }
                $result[$kind][] = $entry;
            }
            $result[$kind] ??= [];
        }

        return $result;
    }

    public function definition(string $kind, string $publicId): array
    {
        new TrustedPrincipal($publicId);
        $table = $this->table($kind);
        $c = $this->database->connection();
        $row = $c->table('runa_permissions.'.$table)->where('public_id', $publicId)->whereNull('deleted_at')->first();
        if ($row === null) {
            $this->reject('access_definition_missing', 404);
        }
        if ($kind === 'permission') {
            return ['public_id' => $row->public_id, 'version' => (int) $row->version,
                'dictionary' => array_intersect_key((array) $row, array_flip(['code', 'name_pl', 'name_en', 'description_pl', 'description_en'])),
                'module' => $row->module_id, 'system' => (bool) $row->system, 'assigned' => (new AssignmentSet)->toArray()];
        }
        $permissions = $c->table('runa_permissions.'.($kind === 'role' ? 'role_permissions' : 'preset_permissions').' as rp')
            ->join('runa_permissions.permissions as p', 'p.id', '=', 'rp.permission_id')->where($kind.'_id', $row->id)
            ->orderBy('p.public_id')->limit(101)->pluck('p.public_id')->all();
        $roles = $kind === 'role' ? [] : $c->table('runa_permissions.preset_roles as pr')->join('runa_permissions.roles as r', 'r.id', '=', 'pr.role_id')
            ->where('preset_id', $row->id)->orderBy('r.public_id')->limit(101)->pluck('r.public_id')->all();
        $set = new AssignmentSet($roles, $permissions);

        return ['public_id' => $row->public_id, 'version' => (int) $row->version,
            'dictionary' => array_intersect_key((array) $row, array_flip(['code', 'name_pl', 'name_en', 'description_pl', 'description_en'])),
            'module' => $kind === 'role' ? $row->module_id : null, 'system' => $kind === 'role' && $row->system,
            'assigned' => $set->toArray()];
    }

    public function effective(AssignmentSet $set, bool $superAdmin = false): array
    {
        $c = $this->database->connection();
        $query = $c->table('runa_permissions.permissions as p')->whereNull('p.deleted_at');
        if (! $superAdmin) {
            $query->where(function ($q) use ($set): void {
                $q->whereIn('p.public_id', $set->permissions)->orWhereIn('p.id', function ($sub) use ($set): void {
                    $sub->select('rp.permission_id')->from('runa_permissions.role_permissions as rp')
                        ->join('runa_permissions.roles as r', 'r.id', '=', 'rp.role_id')->whereNull('r.deleted_at')->whereIn('r.public_id', $set->roles);
                });
            });
        }
        $rows = $query->orderBy('p.code')->limit(1001)->get(['p.code', 'p.module_id']);
        if ($rows->count() > 1000) {
            $this->reject('access_effect_limit');
        }
        $availability = [];
        $result = [];
        foreach ($rows as $row) {
            $available = $row->module_id === null || ($availability[$row->module_id] ??= $this->availability->statuses($row->module_id, 'access.preview')[$row->module_id]->available);
            if ($available) {
                $result[] = $row->code;
            }
        }

        return $result;
    }

    public function fingerprint(mixed $value): string
    {
        return hash('sha256', json_encode($value, JSON_THROW_ON_ERROR));
    }

    public function reject(string $reason, int $status = 422): never
    {
        throw new OperationRejected($reason, 'runa.permissions', 'access', $status);
    }
}
