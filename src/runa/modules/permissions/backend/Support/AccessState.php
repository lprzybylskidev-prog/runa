<?php

namespace Runa\Modules\Permissions\Support;

use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\ModuleFramework\ModuleAvailability;
use Runa\Modules\Auth\Contracts\AuthIdentities;
use Runa\Modules\Permissions\Contracts\AssignmentSet;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationRejected;

/** Owned, bounded projections shared by assignment and definition operations. No grant cache.
 * @phpstan-type ReferenceRow \stdClass&object{public_id: string, code: string, version: int, module_id: string|null}
 * @phpstan-type DefinitionRow \stdClass&object{id: int, public_id: string, code: string, version: int, module_id?: string|null, system?: bool, name_pl: string, name_en: string, description_pl: string, description_en: string}
 */
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

    /** @return array{user_public_id: string, identity_version: int, eligible: bool, security_version: int, revision: int, super_admin: bool, assigned: array{roles: list<string>, permissions: list<string>}, definitions: array{roles: list<array{public_id: string, code: string, version: int, module: string|null, permissions?: list<array{public_id: string, code: string, version: int, module_id: string|null}>}>, permissions: list<array{public_id: string, code: string, version: int, module: string|null, permissions?: list<array{public_id: string, code: string, version: int, module_id: string|null}>}>}} */
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

        /** @var int|null $revision */
        $revision = $c->table('runa_permissions.user_access')->where('user_public_id', $publicId)->value('version');

        return ['user_public_id' => $publicId, 'identity_version' => $identity->version,
            'eligible' => $eligibility->mayOperate(), 'security_version' => $eligibility->securityVersion,
            'revision' => $revision ?? 0,
            'super_admin' => $roles->contains('system', true), 'assigned' => $set->toArray(), 'definitions' => $this->references($set)];
    }

    /** Validate references, including retained disabled-module definitions, without flattening roles.
     * @return array{roles: list<array{public_id: string, code: string, version: int, module: string|null, permissions?: list<array{public_id: string, code: string, version: int, module_id: string|null}>}>, permissions: list<array{public_id: string, code: string, version: int, module: string|null, permissions?: list<array{public_id: string, code: string, version: int, module_id: string|null}>}>} */
    public function references(AssignmentSet $set): array
    {
        $c = $this->database->connection();
        /** @var Collection<int, \stdClass&object{public_id: string, code: string, version: int, module_id: string|null, role_public_id: string}> $members */
        $members = $set->roles === [] ? collect() : $c->table('runa_permissions.role_permissions as rp')
            ->join('runa_permissions.roles as r', 'r.id', '=', 'rp.role_id')
            ->join('runa_permissions.permissions as p', 'p.id', '=', 'rp.permission_id')
            ->whereIn('r.public_id', $set->roles)->whereNull('p.deleted_at')->orderBy('r.public_id')->orderBy('p.public_id')
            ->limit(10001)->get(['r.public_id as role_public_id', 'p.public_id', 'p.code', 'p.version', 'p.module_id']);
        if ($members->count() > 10000) {
            $this->reject('access_reference_limit');
        }
        /** @var Collection<string, Collection<int, ReferenceRow>> $grouped */
        $grouped = $members->groupBy('role_public_id');
        $result = [];
        foreach (['roles' => $set->roles, 'permissions' => $set->permissions] as $kind => $ids) {
            $rows = $c->table('runa_permissions.'.$kind)->whereIn('public_id', $ids)->whereNull('deleted_at')->orderBy('public_id')->get();
            if ($rows->count() !== count($ids) || $rows->contains('system', true)) {
                $this->reject('access_reference_protected_or_missing');
            }
            /** @var ReferenceRow $row */
            foreach ($rows as $row) {
                $entry = ['public_id' => $row->public_id, 'code' => $row->code, 'version' => (int) $row->version, 'module' => $row->module_id];
                if ($kind === 'roles') {
                    $memberRows = $grouped->get($row->public_id);
                    $entry['permissions'] = $memberRows === null ? [] : array_values($memberRows->map(fn ($p) => ['public_id' => $p->public_id, 'code' => $p->code, 'version' => (int) $p->version, 'module_id' => $p->module_id])->all());
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

    /** @return array{public_id: string, version: int, dictionary: array<string, string>, module: string|null, system: bool, assigned: array{roles: list<string>, permissions: list<string>}} */
    public function definition(string $kind, string $publicId): array
    {
        new TrustedPrincipal($publicId);
        $table = $this->table($kind);
        $c = $this->database->connection();
        /** @var DefinitionRow|null $row */
        $row = $c->table('runa_permissions.'.$table)->where('public_id', $publicId)->whereNull('deleted_at')->first();
        if ($row === null) {
            $this->reject('access_definition_missing', 404);
        }
        if ($kind === 'permission') {
            return ['public_id' => $row->public_id, 'version' => (int) $row->version,
                'dictionary' => ['code' => $row->code, 'name_pl' => $row->name_pl, 'name_en' => $row->name_en, 'description_pl' => $row->description_pl, 'description_en' => $row->description_en],
                'module' => ($row->module_id ?? null), 'system' => ($row->system ?? false), 'assigned' => (new AssignmentSet)->toArray()];
        }
        $permissions = $c->table('runa_permissions.'.($kind === 'role' ? 'role_permissions' : 'preset_permissions').' as rp')
            ->join('runa_permissions.permissions as p', 'p.id', '=', 'rp.permission_id')->where($kind.'_id', $row->id)
            ->orderBy('p.public_id')->limit(101)->pluck('p.public_id')->all();
        $roles = $kind === 'role' ? [] : $c->table('runa_permissions.preset_roles as pr')->join('runa_permissions.roles as r', 'r.id', '=', 'pr.role_id')
            ->where('preset_id', $row->id)->orderBy('r.public_id')->limit(101)->pluck('r.public_id')->all();
        $set = new AssignmentSet($roles, $permissions);

        return ['public_id' => $row->public_id, 'version' => (int) $row->version,
            'dictionary' => ['code' => $row->code, 'name_pl' => $row->name_pl, 'name_en' => $row->name_en, 'description_pl' => $row->description_pl, 'description_en' => $row->description_en],
            'module' => $kind === 'role' ? ($row->module_id ?? null) : null, 'system' => $kind === 'role' && ($row->system ?? false),
            'assigned' => $set->toArray()];
    }

    /** @return list<string> */
    public function effective(AssignmentSet $set, bool $superAdmin = false): array
    {
        $c = $this->database->connection();
        $query = $c->table('runa_permissions.permissions as p')->whereNull('p.deleted_at');
        if (! $superAdmin) {
            $query->where(function (Builder $q) use ($set): void {
                $q->whereIn('p.public_id', $set->permissions)->orWhereIn('p.id', function (Builder $sub) use ($set): void {
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
        /** @var \stdClass&object{module_id: string|null, code: string} $row */
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
