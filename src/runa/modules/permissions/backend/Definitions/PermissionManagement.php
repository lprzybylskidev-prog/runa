<?php

namespace Runa\Modules\Permissions\Definitions;

use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\ManagementEntity;
use Runa\Contracts\Modules\ManagementEntityProvider;
use Runa\Contracts\Modules\ManagementField;
use Runa\Contracts\Modules\ManagementOperation;
use Runa\Contracts\Modules\ManagementPermission;

final class PermissionManagement implements ManagementEntityProvider
{
    public function permissions(): array
    {
        return [
            new ManagementPermission('authority.manage', ['pl' => 'Zarządzanie superadministratorami', 'en' => 'Manage super administrators'], ['pl' => 'Osobne nadawanie i odbieranie roli systemowej z ochroną ostatniego administratora.', 'en' => 'Dedicated system-role grant and removal preserving the last eligible administrator.']),
            new ManagementPermission('roles.force_delete', ['pl' => 'Wymuszone usunięcie roli własnej', 'en' => 'Force delete custom roles'], ['pl' => 'Potwierdzone usunięcie po podglądzie użytkowników, presetów i utraty uprawnień.', 'en' => 'Confirmed deletion after reviewing users, presets and practical permission loss.']),
            new ManagementPermission('access.manage', ['pl' => 'Zarządzanie zwykłym dostępem', 'en' => 'Manage ordinary access'], ['pl' => 'Podgląd i zmiana zwykłych przypisań, ról własnych i presetów z potwierdzeniem obu składników.', 'en' => 'Preview and change ordinary assignments, custom roles and presets with fresh both-factor proof.']),
            new ManagementPermission('introspection.read', ['pl' => 'Odczyt uprawnień użytkownika', 'en' => 'Inspect user permissions'], ['pl' => 'Odczyt uprawnień użytkownika.', 'en' => 'Inspect user permissions.']),
        ];
    }

    public function actions(): array
    {
        return [];
    }

    public function components(): array
    {
        return [];
    }

    public function entities(): array
    {
        $entities = [];
        foreach (['role' => ['Rola własna', 'Custom role'], 'preset' => ['Preset dostępu', 'Access preset']] as $kind => [$pl, $en]) {
            $fields = [];
            foreach (['code', 'name_pl', 'name_en', 'description_pl', 'description_en'] as $key) {
                $fields[] = new ManagementField($key, 'text', ['pl' => $key, 'en' => $key],
                    ['pl' => 'Wartość słownika własnego.', 'en' => 'Custom dictionary value.'], writable: true);
            }
            $fields[] = new ManagementField('permission_public_id', 'uuid', ['pl' => 'Uprawnienie', 'en' => 'Permission'], ['pl' => 'Zwykły element przypisania; maksymalnie 100.', 'en' => 'Ordinary assignment member; at most 100.'], writable: true, referenceModule: 'runa.permissions', referenceOperation: 'access.definition');
            $fields[] = new ManagementField('public_id', 'uuid', ['pl' => 'Identyfikator', 'en' => 'Public ID'], ['pl' => 'Niezmienny UUIDv7.', 'en' => 'Immutable UUIDv7.']);
            $fields[] = new ManagementField('version', 'integer', ['pl' => 'Wersja', 'en' => 'Version'], ['pl' => 'Wymagana bieżąca wersja.', 'en' => 'Current version required.']);
            $operations = [
                new ManagementOperation(new ApiOperation('access.save_definition', 'access.manage'),
                    ['code', 'name_pl', 'name_en', 'description_pl', 'description_en'], 'both', previewRequired: true),
                new ManagementOperation(new ApiOperation('access.delete_definition', 'access.manage'), stepUp: 'both'),
            ];
            if ($kind === 'role') {
                $fields[] = new ManagementField('confirmed', 'boolean', ['pl' => 'Potwierdzenie', 'en' => 'Confirmation'], ['pl' => 'Dodatkowe potwierdzenie usunięcia powiązań.', 'en' => 'Extra confirmation of reference removal.'], writable: true);
                $operations[] = new ManagementOperation(new ApiOperation('role.force_delete', 'roles.force_delete'), ['confirmed'], 'both', previewRequired: true);
            }
            $entities[] = new ManagementEntity($kind, 'introspection.read', ['pl' => $pl, 'en' => $en],
                ['pl' => 'Definicje modułowe i systemowe są chronione.', 'en' => 'Module and system definitions remain protected.'], $fields, $operations);
        }
        $entities[] = new ManagementEntity('assignment', 'introspection.read', ['pl' => 'Przypisania', 'en' => 'Assignments'],
            ['pl' => 'Zwykłe przypisania z podglądem różnic; rola systemowa jest wyłączona.', 'en' => 'Ordinary assignments with reviewed differences; system role excluded.'], [
                new ManagementField('user_public_id', 'uuid', ['pl' => 'Użytkownik', 'en' => 'User'], ['pl' => 'Publiczny identyfikator Auth.', 'en' => 'Auth public identity.'], writable: true, referenceModule: 'runa.auth', referenceOperation: 'identity.read'),
                new ManagementField('role_public_id', 'uuid', ['pl' => 'Rola', 'en' => 'Role'], ['pl' => 'Zwykła rola, bez roli systemowej.', 'en' => 'Ordinary role, excluding the system role.'], writable: true, referenceModule: 'runa.permissions', referenceOperation: 'access.definition'),
                new ManagementField('permission_public_id', 'uuid', ['pl' => 'Uprawnienie', 'en' => 'Permission'], ['pl' => 'Uprawnienie bezpośrednie.', 'en' => 'Direct permission.'], writable: true, referenceModule: 'runa.permissions', referenceOperation: 'access.definition'),
                new ManagementField('mode', 'dictionary', ['pl' => 'Tryb', 'en' => 'Mode'], ['pl' => 'Domyślnie add; replace zastępuje zwykłe przypisania.', 'en' => 'Default add; replace replaces ordinary assignments.'], writable: true),
            ], [new ManagementOperation(new ApiOperation('access.apply_assignments', 'access.manage'), ['user_public_id', 'mode'], 'both', previewRequired: true)]);

        $entities[] = new ManagementEntity('authority', 'introspection.read', ['pl' => 'Superadministratorzy', 'en' => 'Super administrators'],
            ['pl' => 'Osobna chroniona operacja; brak obejścia kontroli konta i domeny.', 'en' => 'Dedicated protected operation; account and domain checks remain independent.'], [
                new ManagementField('user_public_id', 'uuid', ['pl' => 'Użytkownik', 'en' => 'User'], ['pl' => 'Publiczny identyfikator Auth.', 'en' => 'Auth public identity.'], writable: true, referenceModule: 'runa.auth', referenceOperation: 'identity.read'),
                new ManagementField('grant', 'boolean', ['pl' => 'Nadaj', 'en' => 'Grant'], ['pl' => 'Nadaj lub odbierz rolę systemową.', 'en' => 'Grant or remove the system role.'], writable: true),
            ], [new ManagementOperation(new ApiOperation('authority.apply', 'authority.manage'), ['user_public_id', 'grant'], 'both', previewRequired: true)]);

        return $entities;
    }
}
