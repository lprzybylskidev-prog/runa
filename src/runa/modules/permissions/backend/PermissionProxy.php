<?php

namespace Runa\Modules\Permissions;

use Runa\Modules\Permissions\Contracts\PermissionPage;
use Runa\Modules\Permissions\Contracts\Permissions;
use Runa\Support\Modules\Api\GuardedApi;

final class PermissionProxy extends GuardedApi implements Permissions
{
    public function allows(string $permission): bool
    {
        /** @var bool $result */
        $result = $this->invoke(__FUNCTION__, [$permission]);

        return $result;
    }

    public function explain(string $userPublicId, ?string $after = null): PermissionPage
    {
        /** @var PermissionPage $result */
        $result = $this->invoke(__FUNCTION__, [$userPublicId, $after]);

        return $result;
    }
}
