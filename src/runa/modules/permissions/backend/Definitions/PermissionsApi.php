<?php

namespace Runa\Modules\Permissions\Definitions;

use Runa\Contracts\Modules\ApiDefinition;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\ApiProvider;
use Runa\Modules\Permissions\Contracts\Permissions;
use Runa\Modules\Permissions\PermissionProxy;
use Runa\Modules\Permissions\Queries\ResolvePermissions;

final class PermissionsApi implements ApiProvider
{
    public function definition(): ApiDefinition
    {
        return new ApiDefinition(Permissions::class, PermissionProxy::class, ResolvePermissions::class, '1.0.0', [
            'allows' => new ApiOperation('permission.check'),
            'explain' => new ApiOperation('permission.explain', 'runa.permissions.introspection.read'),
        ]);
    }
}
