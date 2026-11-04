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

        foreach (['search', 'language', 'selected'] as $filter) {
            $text = ['pl' => $filter, 'en' => $filter];
            $fields[] = new ManagementFieldAccess(new ManagementField($filter, 'text', $text, $text, filterable: true, maxLength: $filter === 'selected' ? 8192 : 160), 'runa.permissions.introspection.read');
        }

        return [new ManagementContribution('user_access', 'runa.auth.users.profile', new ManagementSection('assignments', 'Access',
            'runa.permissions.introspection.read', 'runa.permissions.access.manage', 'assignments', $fields, [
                new ManagementCommandDefinition('save', 'Save access', new ManagementOperation(new ApiOperation('access.apply_assignments', 'runa.permissions.access.manage'),
                    ['roles', 'permissions'], 'both', previewRequired: true), 'assignments', 'atomic', AssignmentEditor::class,
                    intent: 'save', proofAction: 'runa.permissions.access.manage'),
            ]))];
    }

    public function bindings(): array
    {
        return [new ManagementBinding('assignments', AssignmentEditor::class, [
            new ManagementApiTarget('runa.permissions', PermissionsAccess::class, '2.1.0', ['assignments', 'previewAssignments', 'applyAssignments', 'definitions']),
        ], AssignmentEditor::class)];
    }
}
