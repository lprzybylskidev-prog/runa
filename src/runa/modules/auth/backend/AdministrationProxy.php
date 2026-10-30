<?php

namespace Runa\Modules\Auth;

use Runa\Modules\Auth\Contracts\AdministrativeProfile;
use Runa\Modules\Auth\Contracts\AuthAdministration;
use Runa\Modules\Auth\Contracts\UserDirectoryPage;
use Runa\Modules\Auth\Contracts\UserDirectoryQuery;
use Runa\Support\Modules\Api\GuardedApi;

final class AdministrationProxy extends GuardedApi implements AuthAdministration
{
    public function users(UserDirectoryQuery $query): UserDirectoryPage
    {
        /** @var UserDirectoryPage $result */
        $result = $this->invoke(__FUNCTION__, [$query]);

        return $result;
    }

    public function profile(string $publicId): AdministrativeProfile
    {
        /** @var AdministrativeProfile $result */
        $result = $this->invoke(__FUNCTION__, [$publicId]);

        return $result;
    }

    public function lifecycle(string $publicId): array
    {
        /** @var array{public_id:string,version:int,active:bool,approved:bool,deleted:bool,security_locked:bool,password_change_required:bool,activate_at:?string,deactivate_at:?string,expires_at:?string,attempt_locked_until:?string} $result */
        $result = $this->invoke(__FUNCTION__, [$publicId]);

        return $result;
    }
}
