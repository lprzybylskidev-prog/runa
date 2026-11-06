<?php

namespace Runa\Modules\Auth;

use Runa\Modules\Auth\Contracts\AuthLifecycle;
use Runa\Modules\Auth\Contracts\Identity;
use Runa\Modules\Auth\Contracts\IdentitySetup;
use Runa\Modules\Auth\Contracts\LifecycleChange;
use Runa\Modules\Auth\Contracts\NewIdentity;
use Runa\Support\Modules\Api\GuardedApi;

final class LifecycleProxy extends GuardedApi implements AuthLifecycle
{
    public function create(NewIdentity $input, #[\SensitiveParameter] ?IdentitySetup $setup = null): Identity
    {
        /** @var Identity $result */
        $result = $this->invoke(__FUNCTION__, [$input, $setup]);

        return $result;
    }

    public function change(string $publicId, int $expectedVersion, LifecycleChange $change): Identity
    {
        /** @var Identity $result */
        $result = $this->invoke(__FUNCTION__, [$publicId, $expectedVersion, $change]);

        return $result;
    }
}
