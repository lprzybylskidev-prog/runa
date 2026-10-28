<?php

namespace Runa\Modules\Auth;

use Runa\Modules\Auth\Contracts\AuthSessions;
use Runa\Support\Modules\Api\GuardedApi;

final class SessionsProxy extends GuardedApi implements AuthSessions
{
    public function listFor(string $publicId, ?string $before = null): array
    {
        /** @var list<array{public_id: string, current: bool, remembered: bool, authenticated_at: string, activity_at: string, expires_at: string}> $result */
        $result = $this->invoke(__FUNCTION__, [$publicId, $before]);

        return $result;
    }

    public function revoke(string $publicId, string $sessionId): bool
    {
        /** @var bool $result */
        $result = $this->invoke(__FUNCTION__, [$publicId, $sessionId]);

        return $result;
    }

    public function revokeAll(string $publicId): bool
    {
        /** @var bool $result */
        $result = $this->invoke(__FUNCTION__, [$publicId]);

        return $result;
    }
}
