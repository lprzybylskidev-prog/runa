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
use Runa\Modules\Permissions\Contracts\Permissions;
use Runa\Modules\Permissions\Contracts\PermissionsAccess;
use Runa\Modules\Permissions\Contracts\PermissionsAuthority;

/** Source-owned catalogue; metadata never supplies an actor or executes a method. */
final class AdministrationPages implements ManagementPageProvider
{
    /** @var array<string, string> */
    private const FIELD_NAMES = [
        'Access' => 'Dostęp',
        'Account access' => 'Dostęp konta',
        'Affected accounts, presets and effective losses' => 'Konta i zestawy objęte zmianą oraz utracone uprawnienia',
        'Apply access preset' => 'Zastosuj zestaw uprawnień',
        'Apply ordinary assignments' => 'Zastosuj zwykłe przypisania',
        'Apply preset UUID' => 'UUID zestawu do zastosowania',
        'Assignment mode' => 'Tryb przypisania',
        'Change protected authority' => 'Zmień chronione uprawnienia',
        'Code' => 'Kod',
        'Copy from account UUID' => 'UUID konta źródłowego',
        'Copy ordinary assignments' => 'Kopiuj zwykłe przypisania',
        'Definitions' => 'Definicje',
        'Description En' => 'Opis (EN)',
        'Description Pl' => 'Opis (PL)',
        'Direct permissions' => 'Bezpośrednie uprawnienia',
        'Effective changes and affected references' => 'Wynikowe zmiany i powiązania objęte zmianą',
        'Effective permissions' => 'Efektywne uprawnienia',
        'Force delete referenced custom role' => 'Wymuś usunięcie własnej roli z powiązaniami',
        'Force deletion' => 'Wymuszone usunięcie',
        'Grant protected membership' => 'Nadaj chronione członkostwo',
        'Module' => 'Moduł',
        'Name' => 'Nazwa',
        'Name En' => 'Nazwa (EN)',
        'Name Pl' => 'Nazwa (PL)',
        'New preset from account UUID' => 'Nowy zestaw z konta o UUID',
        'Next permission cursor' => 'Kursor następnej strony uprawnień',
        'Ordinary access' => 'Zwykły dostęp',
        'Ordinary roles' => 'Zwykłe role',
        'Permission' => 'Uprawnienie',
        'Permissions' => 'Uprawnienia',
        'Practical effective changes' => 'Rzeczywiste zmiany uprawnień',
        'Preset' => 'Zestaw',
        'Presets' => 'Zestawy',
        'Protected authority' => 'Chronione uprawnienia',
        'Protected definition' => 'Definicja chroniona',
        'Protected membership and eligibility review' => 'Przegląd chronionego członkostwa i warunków dostępu',
        'Protected system definition' => 'Chroniona definicja systemowa',
        'Role' => 'Rola',
        'Roles' => 'Role',
        'Save' => 'Zapisz',
        'Save access' => 'Zapisz dostęp',
        'Source owner' => 'Właściciel źródła',
        'Users' => 'Użytkownicy',
        'add' => 'dodaj',
        'code' => 'kod',
        'committed' => 'zatwierdzono',
        'delete' => 'usuń',
        'effective' => 'efektywne',
        'public_id' => 'publiczny UUID',
        'replace' => 'zastąp',
        'revision' => 'rewizja',
        'roles' => 'role',
        'value' => 'wartość',
        'version' => 'wersja',
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

    public static function command(string $key, string $title, string $operation, array $inputs, string $binding, string $permission = 'access.manage', bool $destructive = false, bool $review = true): ManagementCommandDefinition
    {
        return new ManagementCommandDefinition($key, $title, new ManagementOperation(new ApiOperation($operation, 'runa.permissions.'.$permission), $inputs, 'both', previewRequired: $review), $binding,
            destructive: $destructive, proofAction: 'runa.permissions.'.$permission);
    }

    public function pages(): array
    {
        $pages = [];
        foreach (['role', 'preset', 'permission'] as $kind) {
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
                    $fields[] = self::field('source_user', 'New preset from account UUID', 'uuid', 'access.manage');
                }
                $fields[] = self::field('review_summary', 'Effective changes and affected references');
            }
            $inputs = ['code', 'name_pl', 'name_en', 'description_pl', 'description_en', 'permissions'];
            if ($kind === 'preset') {
                $inputs = [...$inputs, 'roles', 'source_user'];
            }
            $commands = $kind === 'permission' ? [] : [
                self::command('store', 'Save custom '.$kind, 'access.save_definition', $inputs, $binding),
                self::command('delete', 'Delete custom '.$kind, 'access.delete_definition', [], $binding, destructive: true),
            ];
            $pages[] = new ManagementPage($kind.'s', ucfirst($kind).'s', 'runa.permissions.introspection.read', [
                new ManagementSection('catalogue', 'Definitions', 'runa.permissions.introspection.read', null, $binding,
                    [self::field('code', 'Code', table: true), self::field('name_pl', 'Name Pl', table: true), self::field('name_en', 'Name En', table: true)], view: 'table'),
                new ManagementSection('editor', ucfirst($kind).' details', 'runa.permissions.introspection.read', $kind === 'permission' ? null : 'runa.permissions.access.manage', $binding,
                    $fields, $commands, component: 'runa.permissions.admin_definition'),
                ...($kind === 'role' ? [new ManagementSection('force', 'Force deletion', 'runa.permissions.introspection.read', 'runa.permissions.roles.force_delete', $binding, [self::field('module', 'Source owner'), self::field('system', 'Protected definition', 'boolean'), self::field('review_summary', 'Affected accounts, presets and effective losses')], [self::command('force_delete', 'Force delete referenced custom role', 'role.force_delete', [], $binding, 'roles.force_delete', true)], required: false, component: 'runa.permissions.admin_definition')] : []),
            ], component: 'runa.permissions.admin_definitions');
        }
        $binding = 'access_editor';
        $pages[] = new ManagementPage('access', 'Account access', 'runa.permissions.introspection.read', [
            new ManagementSection('access', 'Ordinary access', 'runa.permissions.introspection.read', 'runa.permissions.access.manage', $binding, [
                self::field('roles', 'Ordinary roles', 'uuid', 'access.manage', true), self::field('permissions', 'Direct permissions', 'uuid', 'access.manage', true),
                self::field('mode', 'Assignment mode', 'dictionary', 'access.manage', choices: ['add', 'replace']),
                self::field('source_user', 'Copy from account UUID', 'uuid', 'access.manage'), self::field('preset', 'Apply preset UUID', 'uuid', 'access.manage'),
                self::field('review_summary', 'Practical effective changes'),
            ], [self::command('assign', 'Apply ordinary assignments', 'access.apply_assignments', ['roles', 'permissions', 'mode'], $binding),
                self::command('copy', 'Copy ordinary assignments', 'access.apply_assignments', ['source_user', 'mode'], $binding),
                self::command('preset', 'Apply access preset', 'access.apply_assignments', ['preset', 'mode'], $binding)]),
            new ManagementSection('effective', 'Effective permissions', 'runa.permissions.introspection.read', null, 'effective', [
                self::field('entries', 'Effective permissions'), self::field('next', 'Next permission cursor'),
            ], required: false, component: 'runa.permissions.admin_effective'),
            new ManagementSection('authority', 'Protected authority', 'runa.permissions.authority.manage', 'runa.permissions.authority.manage', 'authority', [
                self::field('grant', 'Grant protected membership', 'boolean', 'authority.manage'),
                self::field('review_summary', 'Protected membership and eligibility review'),
            ], [self::command('change', 'Change protected authority', 'authority.apply', ['grant'], 'authority', 'authority.manage', true)], required: false),
        ], component: 'runa.permissions.admin_access');

        return $pages;
    }

    public function bindings(): array
    {
        $access = new ManagementApiTarget('runa.permissions', PermissionsAccess::class, '2.2.0', ['assignments', 'previewAssignments', 'previewCopy', 'previewPreset', 'applyAssignments', 'definitions', 'definition', 'previewDefinition', 'saveDefinition', 'deleteDefinition']);
        $authority = new ManagementApiTarget('runa.permissions', PermissionsAuthority::class, '1.0.0', ['previewAuthority', 'applyAuthority', 'previewRoleDeletion', 'forceDeleteRole']);

        return [new ManagementBinding('role_editor', RoleEditor::class, [$access, $authority]),
            new ManagementBinding('preset_editor', PresetEditor::class, [$access]), new ManagementBinding('permission_editor', PermissionEditor::class, [$access]),
            new ManagementBinding('access_editor', AccessEditor::class, [$access]), new ManagementBinding('authority', AuthorityEditor::class, [$authority]),
            new ManagementBinding('effective', EffectivePermissions::class, [new ManagementApiTarget('runa.permissions', Permissions::class, '1.0.0', ['explain'])])];
    }
}
