<?php

namespace Runa\Modules\Permissions\Definitions;

use Runa\Contracts\Modules\ApiDefinition;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\ApiProvider;
use Runa\Modules\Permissions\Actions\ManageAuthority;
use Runa\Modules\Permissions\AuthorityProxy;
use Runa\Modules\Permissions\Contracts\PermissionsAuthority;

final class AuthorityApi implements ApiProvider
{
    public function definition(): ApiDefinition
    {
        return new ApiDefinition(PermissionsAuthority::class, AuthorityProxy::class, ManageAuthority::class, '1.0.0', [
            'previewAuthority' => new ApiOperation('authority.preview', 'runa.permissions.authority.manage'),
            'applyAuthority' => new ApiOperation('authority.apply', 'runa.permissions.authority.manage'),
            'previewRoleDeletion' => new ApiOperation('role.preview_delete', 'runa.permissions.roles.force_delete'),
            'forceDeleteRole' => new ApiOperation('role.force_delete', 'runa.permissions.roles.force_delete'),
        ]);
    }
}
