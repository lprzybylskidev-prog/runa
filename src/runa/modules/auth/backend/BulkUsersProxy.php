<?php

namespace Runa\Modules\Auth;

use Runa\Modules\Auth\Contracts\AuthBulkUsers;
use Runa\Modules\Auth\Contracts\BulkUserChange;
use Runa\Modules\Auth\Contracts\SessionSelection;
use Runa\Support\Modules\Api\GuardedApi;

final class BulkUsersProxy extends GuardedApi implements AuthBulkUsers
{
    public function preview(SessionSelection $selection, BulkUserChange $change): array
    {
        /** @var array{fingerprint:string,accounts:int,affected_accounts:int,sessions:int,trusted_devices:int} $result */
        $result = $this->invoke(__FUNCTION__, [$selection, $change]);

        return $result;
    }

    public function execute(SessionSelection $selection, BulkUserChange $change, string $fingerprint): array
    {
        /** @var array{accounts:int} $result */
        $result = $this->invoke(__FUNCTION__, [$selection, $change, $fingerprint]);

        return $result;
    }
}
