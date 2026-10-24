<?php

namespace Runa\Modules\Permissions\Definitions;

use Runa\Contracts\Modules\ApiDefinition;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\ApiProvider;
use Runa\Modules\Permissions\AccessProxy;
use Runa\Modules\Permissions\Actions\ManageAccess;
use Runa\Modules\Permissions\Contracts\PermissionsAccess;

final class AccessApi implements ApiProvider
{
    public function definition(): ApiDefinition
    {
        return new ApiDefinition(PermissionsAccess::class, AccessProxy::class, ManageAccess::class, '1.0.0', [
            'assignments' => new ApiOperation('access.assignments', 'runa.permissions.introspection.read'),
            'previewAssignments' => new ApiOperation('access.preview_assignments', 'runa.permissions.access.manage'),
            'previewCopy' => new ApiOperation('access.preview_copy', 'runa.permissions.access.manage'),
            'previewPreset' => new ApiOperation('access.preview_preset', 'runa.permissions.access.manage'),
            'applyAssignments' => new ApiOperation('access.apply_assignments', 'runa.permissions.access.manage'),
            'definitions' => new ApiOperation('access.definitions', 'runa.permissions.introspection.read'),
            'definition' => new ApiOperation('access.definition', 'runa.permissions.introspection.read'),
            'previewDefinition' => new ApiOperation('access.preview_definition', 'runa.permissions.access.manage'),
            'saveDefinition' => new ApiOperation('access.save_definition', 'runa.permissions.access.manage'),
            'deleteDefinition' => new ApiOperation('access.delete_definition', 'runa.permissions.access.manage'),
        ]);
    }
}
