<?php

namespace Runa\Modules\Auth;

use Runa\Modules\Auth\Contracts\AuthIdentities;
use Runa\Modules\Auth\Contracts\Eligibility;
use Runa\Modules\Auth\Contracts\Identity;
use Runa\Support\Modules\Api\GuardedApi;

final class IdentityProxy extends GuardedApi implements AuthIdentities
{
    public function identity(string $publicId): ?Identity
    {
        /** @var ?Identity $result */
        $result = $this->invoke(__FUNCTION__, [$publicId]);

        return $result;
    }

    public function eligibility(string $publicId): ?Eligibility
    {
        /** @var ?Eligibility $result */
        $result = $this->invoke(__FUNCTION__, [$publicId]);

        return $result;
    }
}
