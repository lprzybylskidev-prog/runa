<?php

namespace Runa\Modules\Permissions\Management;

use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\ManagementApiTarget;
use Runa\Contracts\Modules\ManagementBinding;
use Runa\Contracts\Modules\ManagementCommandDefinition;
use Runa\Contracts\Modules\ManagementField;
use Runa\Contracts\Modules\ManagementFieldAccess;
use Runa\Contracts\Modules\ManagementOperation;
use Runa\Contracts\Modules\ManagementPage;
use Runa\Contracts\Modules\ManagementPageProvider;
use Runa\Contracts\Modules\ManagementSection;
use Runa\Modules\Permissions\Contracts\PermissionsAccess;
use Runa\Modules\Permissions\Contracts\PermissionsAuthority;

/** Source-owned catalogue; metadata never supplies an actor or executes a method. */
final class AdministrationPages implements ManagementPageProvider
{
    /** @var array<string, string> */
    private const FIELD_NAMES = [
        'Access' => 'Dostęp',
        'Affected accounts, presets and effective losses' => 'Konta i zestawy objęte zmianą oraz utracone uprawnienia',
        'Apply access preset' => 'Zastosuj szablon',
        'Change protected authority' => 'Zmień chronione uprawnienia',
        'Code' => 'Kod',
        'Description Pl' => 'Opis (PL)',
        'Description En' => 'Opis (EN)',
        'Delete' => 'Usuń',
        'Copy ordinary assignments' => 'Kopiuj zwykłe przypisania',
        'Definitions' => 'Definicje',
        'Direct permissions' => 'Bezpośrednie uprawnienia',
        'Effective changes and affected references' => 'Wynikowe zmiany i powiązania objęte zmianą',
        'Force delete referenced custom role' => 'Wymuś usunięcie własnej roli z powiązaniami',
        'Force deletion' => 'Wymuszone usunięcie',
        'Grant protected membership' => 'Nadaj chronione członkostwo',
        'Name' => 'Nazwa',
        'Name En' => 'Nazwa (EN)',
        'Name Pl' => 'Nazwa (PL)',
        'Ordinary roles' => 'Zwykłe role',
        'Permissions' => 'Uprawnienia',
        'Preset' => 'Zestaw',
        'Presets' => 'Zestawy',
        'Protected definition' => 'Definicja chroniona',
        'Protected system definition' => 'Chroniona definicja systemowa',
        'Role' => 'Rola',
        'Roles' => 'Role',
        'Save' => 'Zapisz',
        'Save access' => 'Zapisz dostęp',
        'Source owner' => 'Właściciel źródła',
        'add' => 'dodaj',
        'code' => 'kod',
        'delete' => 'usuń',
        'public_id' => 'publiczny UUID',
        'replace' => 'zastąp',
        'revision' => 'rewizja',
        'roles' => 'role',
    ];

    public function permissions(): array
    {
        return [];
    }

    public function actions(): array
    {
        return [];
    }

    public function components(): array
    {
        return [];
    }

    public function contributions(): array
    {
        return [];
    }

    public function widgets(): array
    {
        return [];
    }

    public static function field(string $key, string $title, string $type = 'text', ?string $write = null, bool $multiple = false, array $choices = [], bool $table = false): ManagementFieldAccess
    {
        $text = ['pl' => self::FIELD_NAMES[$title] ?? $title, 'en' => $title];
        $reference = in_array($key, ['roles', 'permissions'], true);

        return new ManagementFieldAccess(new ManagementField($key, $type, $text, $text, filterable: $table, sortable: $table, writable: $write !== null,
            required: $write !== null && $type === 'text',
            maxLength: $type === 'text' && $write !== null ? ($key === 'code' ? 71 : (str_starts_with($key, 'description_') ? 1000 : 160)) : null,
            choices: $choices, referenceModule: $reference ? 'runa.permissions' : null,
            referenceOperation: $reference ? 'access.definitions' : null, multiple: $multiple),
            'runa.permissions.introspection.read', $write === null ? null : 'runa.permissions.'.$write);
    }

    /** @return list<ManagementFieldAccess> */
    public static function catalogueFilters(): array
    {
        return array_map(static function (string $key): ManagementFieldAccess {
            $text = ['pl' => $key, 'en' => $key];

            return new ManagementFieldAccess(new ManagementField($key, 'text', $text, $text, filterable: true, maxLength: $key === 'selected' ? 8192 : 160), 'runa.permissions.introspection.read');
        }, ['search', 'language', 'selected']);
    }

    public static function command(string $key, string $title, string $operation, array $inputs, string $binding, string $permission = 'access.manage', bool $destructive = false, bool $review = true, ?string $participant = null): ManagementCommandDefinition
    {
        return new ManagementCommandDefinition($key, ['pl' => self::FIELD_NAMES[$title] ?? $title, 'en' => $title], new ManagementOperation(new ApiOperation($operation, 'runa.permissions.'.$permission), $inputs, 'password', previewRequired: $review), $binding,
            participant: $participant, destructive: $destructive, proofAction: 'runa.permissions.'.$permission);
    }

    public function pages(): array
    {
        $pages = [];
        foreach (['permission', 'role', 'preset'] as $kind) {
            $binding = $kind.'_editor';
            $fields = [];
            foreach (['code', 'name_pl', 'name_en', 'description_pl', 'description_en'] as $key) {
                $fields[] = self::field($key, ucwords(str_replace('_', ' ', $key)), write: $kind === 'permission' ? null : 'access.manage');
            }
            $fields[] = self::field('module', 'Source owner');
            $fields[] = self::field('system', 'Protected system definition', 'boolean');
            if ($kind !== 'permission') {
                $fields[] = self::field('permissions', 'Direct permissions', 'uuid', 'access.manage', true);
                if ($kind === 'preset') {
                    $fields[] = self::field('roles', 'Ordinary roles', 'uuid', 'access.manage', true);
                }
                $fields[] = self::field('review_summary', 'Effective changes and affected references');
            }
            $inputs = ['code', 'name_pl', 'name_en', 'description_pl', 'description_en', 'permissions'];
            if ($kind === 'preset') {
                $inputs = [...$inputs, 'roles'];
            }
            $commands = $kind === 'permission' ? [] : [
                self::command('store', 'Save', 'access.save_definition', $inputs, $binding),
                self::command('delete', 'Delete', 'access.delete_definition', [], $binding, destructive: true),
            ];
            $pages[] = new ManagementPage($kind.'s', match ($kind) {
                'role' => ['pl' => 'Role', 'en' => 'Roles'], 'preset' => ['pl' => 'Szablony', 'en' => 'Presets'], default => ['pl' => 'Uprawnienia', 'en' => 'Permissions']
            }, 'runa.permissions.introspection.read', [
                new ManagementSection('catalogue', 'Definitions', 'runa.permissions.introspection.read', null, $binding,
                    [self::field('code', 'Code', table: true), self::field('name_pl', 'Name Pl', table: true), self::field('name_en', 'Name En', table: true)], view: 'table'),
                new ManagementSection('editor', match ($kind) {
                    'role' => ['pl' => 'Szczegóły roli', 'en' => 'Role details'], 'preset' => ['pl' => 'Szczegóły szablonu', 'en' => 'Preset details'], default => ['pl' => 'Szczegóły uprawnienia', 'en' => 'Permission details']
                }, 'runa.permissions.introspection.read', $kind === 'permission' ? null : 'runa.permissions.access.manage', $binding,
                    [...$fields, ...self::catalogueFilters()], $commands, component: 'runa.permissions.admin_definition'),
                ...($kind === 'role' ? [new ManagementSection('force', 'Force deletion', 'runa.permissions.introspection.read', 'runa.permissions.roles.force_delete', $binding, [self::field('module', 'Source owner'), self::field('system', 'Protected definition', 'boolean'), self::field('review_summary', 'Affected accounts, presets and effective losses')], [self::command('force_delete', 'Force delete referenced custom role', 'role.force_delete', [], $binding, 'roles.force_delete', true)], required: false, component: 'runa.permissions.admin_definition')] : []),
            ], order: match ($kind) {
                'permission' => 0, 'role' => 10, default => 20
            }, default: $kind === 'permission', component: 'runa.permissions.admin_definitions');
        }

        return $pages;
    }

    public function bindings(): array
    {
        $access = new ManagementApiTarget('runa.permissions', PermissionsAccess::class, '2.2.0', ['definitions', 'definition', 'previewDefinition', 'saveDefinition', 'deleteDefinition']);
        $authority = new ManagementApiTarget('runa.permissions', PermissionsAuthority::class, '1.0.0', ['previewRoleDeletion', 'forceDeleteRole']);

        return [new ManagementBinding('role_editor', RoleEditor::class, [$access, $authority]),
            new ManagementBinding('preset_editor', PresetEditor::class, [$access]), new ManagementBinding('permission_editor', PermissionEditor::class, [$access]),
        ];
    }
}
