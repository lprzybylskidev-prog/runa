<?php

namespace Runa\Modules\Auth;

use Runa\Modules\Auth\Contracts\AuthSessions;
use Runa\Support\Modules\Api\GuardedApi;

final class SessionsProxy extends GuardedApi implements AuthSessions
{
    public function listFor(string $publicId, ?string $before = null): array
    {
        return $this->invoke(__FUNCTION__, [$publicId, $before]);
    }

    public function revoke(string $publicId, string $sessionId): bool
    {
        return $this->invoke(__FUNCTION__, [$publicId, $sessionId]);
    }

    public function revokeAll(string $publicId): bool
    {
        return $this->invoke(__FUNCTION__, [$publicId]);
    }
}
