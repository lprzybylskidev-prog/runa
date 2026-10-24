<?php

namespace Runa\Modules\Permissions;

use Runa\Modules\Permissions\Contracts\PermissionsSelections;
use Runa\Modules\Permissions\Contracts\UserSelection;
use Runa\Support\Modules\Api\GuardedApi;

final class SelectionProxy extends GuardedApi implements PermissionsSelections
{
    public function users(UserSelection $selection): UserSelection
    {
        return $this->invoke(__FUNCTION__, [$selection]);
    }
}
