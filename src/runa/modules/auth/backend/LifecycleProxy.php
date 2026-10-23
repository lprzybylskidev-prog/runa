<?php

namespace Runa\Modules\Auth;

use Runa\Modules\Auth\Contracts\AuthLifecycle;
use Runa\Modules\Auth\Contracts\Identity;
use Runa\Modules\Auth\Contracts\LifecycleChange;
use Runa\Modules\Auth\Contracts\NewIdentity;
use Runa\Support\Modules\Api\GuardedApi;

final class LifecycleProxy extends GuardedApi implements AuthLifecycle
{
    public function create(NewIdentity $input): Identity
    {
        return $this->invoke(__FUNCTION__, [$input]);
    }

    public function change(string $publicId, int $expectedVersion, LifecycleChange $change): Identity
    {
        return $this->invoke(__FUNCTION__, [$publicId, $expectedVersion, $change]);
    }
}
