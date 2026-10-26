<?php

namespace Runa\Modules\Permissions\Definitions;

use Runa\Contracts\Modules\ApiDefinition;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\ApiProvider;
use Runa\Modules\Permissions\Contracts\PermissionsSelections;
use Runa\Modules\Permissions\Queries\SelectUsers;
use Runa\Modules\Permissions\SelectionProxy;

final class SelectionApi implements ApiProvider
{
    public function definition(): ApiDefinition
    {
        return new ApiDefinition(PermissionsSelections::class, SelectionProxy::class, SelectUsers::class, '1.0.0', [
            'users' => new ApiOperation('selection.users', 'runa.permissions.users.select'),
        ]);
    }
}
