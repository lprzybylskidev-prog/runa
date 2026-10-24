<?php

namespace Runa\Modules\Permissions\Support;

use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\DictionaryEntry;
use Runa\Contracts\Modules\ManagementPermission;
use Runa\Contracts\Modules\ManagementProvider;
use Runa\Contracts\Modules\RoleDefinition;
use Runa\Contracts\Modules\RoleProvider;
use Runa\Support\Modules\Api\ModuleDeclarations;
use Runa\Support\Modules\ModuleRegistry;

/** Pure source reads through existing owned management descriptors. No boot-time writes. */
final readonly class DeclarationCatalog
{
    public function __construct(private ModuleRegistry $registry, private ModuleDeclarations $declarations) {}

    /** @return array{permissions: array<string, array<string, string>>, roles: array<string, array{dictionary: array<string, string>, permissions: list<string>}>} */
    public function read(string $module): array
    {
        $record = $this->registry->snapshot()->modules[$module] ?? throw new \RuntimeException('Declaration owner unavailable.');
        $permissions = $roles = [];
        foreach ($this->declarations->classes($record, 'management') as $class) {
            $provider = $this->declarations->make($record, $class, ManagementProvider::class);
            if (! $provider instanceof ManagementProvider) {
                throw new \RuntimeException('Invalid permission provider.');
            }
            $values = $provider->permissions();
            if (! array_is_list($values) || count($values) > 128) {
                throw new \RuntimeException('Invalid permission declarations.');
            }
            foreach ($values as $permission) {
                if (! $permission instanceof ManagementPermission || count($permissions) >= 128) {
                    throw new \RuntimeException('Invalid permission declaration.');
                }
                $code = $this->code($module, $permission->key);
                if (isset($permissions[$code])) {
                    throw new \RuntimeException('Duplicate permission declaration.');
                }
                $permissions[$code] = DictionaryEntry::fromLocalized($code, $permission->names, $permission->descriptions)->toRow();
                $this->validateDictionary($permissions[$code]);
            }
            if ($provider instanceof RoleProvider) {
                $values = $provider->roles();
                if (! array_is_list($values) || count($values) > 32) {
                    throw new \RuntimeException('Invalid role declarations.');
                }
                foreach ($values as $role) {
                    if (! $role instanceof RoleDefinition || count($roles) >= 32) {
                        throw new \RuntimeException('Invalid role declaration.');
                    }
                    $code = $this->code($module, $role->dictionary->code);
                    if (isset($roles[$code]) || $code === 'runa.permissions.super_admin') {
                        throw new \RuntimeException('Duplicate or reserved role declaration.');
                    }
                    $members = array_map(fn (string $key): string => $this->code($module, $key), $role->permissions);
                    sort($members);
                    $roles[$code] = ['dictionary' => array_replace($role->dictionary->toRow(), ['code' => $code]), 'permissions' => $members];
                    $this->validateDictionary($roles[$code]['dictionary']);
                }
            }
        }
        foreach ($roles as $role) {
            foreach ($role['permissions'] as $permission) {
                if (! isset($permissions[$permission])) {
                    throw new \RuntimeException('Predefined roles require owned declared permissions.');
                }
            }
        }
        ksort($permissions);
        ksort($roles);

        return ['permissions' => $permissions, 'roles' => $roles];
    }

    private function code(string $module, string $key): string
    {
        // Keys are local, never another owner's fully qualified identity.
        if (str_starts_with($key, 'runa.') || str_starts_with($key, 'app.')) {
            throw new \RuntimeException('Permission declarations require local keys.');
        }
        $code = $module.'.'.$key;
        new ApiOperation($code);

        return $code;
    }

    /** @param array<string, string> $dictionary */
    private function validateDictionary(array $dictionary): void
    {
        foreach (['name_pl', 'name_en', 'description_pl', 'description_en'] as $key) {
            if (mb_strlen($dictionary[$key]) > (str_starts_with($key, 'name_') ? 160 : 1000)) {
                throw new \RuntimeException('Declaration dictionary exceeds storage limits.');
            }
        }
    }
}
