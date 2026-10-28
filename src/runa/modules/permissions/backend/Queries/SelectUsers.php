<?php

namespace Runa\Modules\Permissions\Queries;

use Runa\Contracts\Modules\ApiOperation;
use Runa\ModuleFramework\Api\OperationGuard;
use Runa\Modules\Permissions\Contracts\PermissionsSelections;
use Runa\Modules\Permissions\Contracts\UserSelection;

final readonly class SelectUsers implements PermissionsSelections
{
    public function __construct(private OperationGuard $guard) {}

    public function users(UserSelection $selection): UserSelection
    {
        $this->guard->enter('runa.permissions', new ApiOperation('selection.users', 'runa.permissions.users.select'));
        $ids = $selection->publicIds;
        sort($ids);

        return new UserSelection($ids, $selection->all);
    }
}
