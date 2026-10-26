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
        return $this->invoke(__FUNCTION__, [$publicId]);
    }

    public function eligibility(string $publicId): ?Eligibility
    {
        return $this->invoke(__FUNCTION__, [$publicId]);
    }
}
