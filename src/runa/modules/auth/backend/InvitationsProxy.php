<?php

namespace Runa\Modules\Auth;

use Runa\Modules\Auth\Contracts\AuthInvitations;
use Runa\Support\Modules\Api\GuardedApi;

final class InvitationsProxy extends GuardedApi implements AuthInvitations
{
    public function invite(string $publicId, int $expectedVersion): bool
    {
        return $this->invoke(__FUNCTION__, [$publicId, $expectedVersion]);
    }

    public function invalidate(string $publicId, int $expectedVersion): bool
    {
        return $this->invoke(__FUNCTION__, [$publicId, $expectedVersion]);
    }
}
