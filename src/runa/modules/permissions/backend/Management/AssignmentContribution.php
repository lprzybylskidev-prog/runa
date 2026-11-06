<?php

namespace Runa\Modules\Permissions\Management;

use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\ManagementApiTarget;
use Runa\Contracts\Modules\ManagementBinding;
use Runa\Contracts\Modules\ManagementCommandDefinition;
use Runa\Contracts\Modules\ManagementContribution;
use Runa\Contracts\Modules\ManagementField;
use Runa\Contracts\Modules\ManagementFieldAccess;
use Runa\Contracts\Modules\ManagementOperation;
use Runa\Contracts\Modules\ManagementPageProvider;
use Runa\Contracts\Modules\ManagementSection;
use Runa\Modules\Auth\Contracts\AuthAdministration;
use Runa\Modules\Permissions\Contracts\PermissionsAccess;

final class AssignmentContribution implements ManagementPageProvider
{
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

    public function pages(): array
    {
        return [];
    }

    public function widgets(): array
    {
        return [];
    }

    public function contributions(): array
    {
        $fields = [];
        foreach (['roles' => 'Roles', 'permissions' => 'Permissions'] as $key => $label) {
            $fields[] = AdministrationPages::field($key, $label, 'uuid', 'access.manage', multiple: true);
        }

        $fields[] = new ManagementFieldAccess(new ManagementField('source_user', 'uuid', ['pl' => 'Użytkownik źródłowy', 'en' => 'Source user'], ['pl' => 'Kopiuj zwykłe role i uprawnienia.', 'en' => 'Copy ordinary roles and permissions.'], writable: true, referenceModule: 'runa.auth', referenceOperation: 'administration.users'), 'runa.auth.users.read', 'runa.permissions.access.manage');
        $fields[] = new ManagementFieldAccess(new ManagementField('preset', 'uuid', ['pl' => 'Szablon', 'en' => 'Preset'], ['pl' => 'Zapisany szablon dostępu.', 'en' => 'Saved access preset.'], writable: true, referenceModule: 'runa.permissions', referenceOperation: 'access.definitions'), 'runa.permissions.introspection.read', 'runa.permissions.access.manage');
        $fields[] = new ManagementFieldAccess(new ManagementField('mode', 'dictionary', ['pl' => 'Sposób zastosowania', 'en' => 'Application mode'], ['pl' => 'Dodaj albo zastąp zwykłe przypisania.', 'en' => 'Add or replace ordinary assignments.'], writable: true, choices: ['add', 'replace']), 'runa.permissions.introspection.read', 'runa.permissions.access.manage');

        $fields = [...$fields, ...AdministrationPages::catalogueFilters()];

        return [new ManagementContribution('user_access', 'runa.auth.users.profile', new ManagementSection('assignments', 'Access',
            'runa.permissions.introspection.read', 'runa.permissions.access.manage', 'assignments', $fields, [
                new ManagementCommandDefinition('save', 'Save access', new ManagementOperation(new ApiOperation('access.apply_assignments', 'runa.permissions.access.manage'),
                    ['roles', 'permissions'], 'password', previewRequired: true), 'assignments', 'atomic', AssignmentEditor::class,
                    intent: 'save', proofAction: 'runa.permissions.access.manage'),
                AdministrationPages::command('copy', 'Copy ordinary assignments', 'access.apply_assignments', ['source_user', 'mode'], 'assignments', participant: AssignmentEditor::class),
                AdministrationPages::command('preset', 'Apply access preset', 'access.apply_assignments', ['preset', 'mode'], 'assignments', participant: AssignmentEditor::class),
            ]))];
    }

    public function bindings(): array
    {
        return [new ManagementBinding('assignments', AssignmentEditor::class, [
            new ManagementApiTarget('runa.permissions', PermissionsAccess::class, '2.2.0', ['assignments', 'previewAssignments', 'previewCopy', 'previewPreset', 'applyAssignments', 'definitions']),
            new ManagementApiTarget('runa.auth', AuthAdministration::class, '2.1.0', ['users']),
        ], AssignmentEditor::class)];
    }
}
