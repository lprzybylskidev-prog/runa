<?php

namespace Runa\Modules\Auth;

use Runa\Modules\Auth\Contracts\AuthSecurity;
use Runa\Support\Modules\Api\GuardedApi;

final class SecurityProxy extends GuardedApi implements AuthSecurity
{
    public function requestPasswordReset(string $publicId, int $expectedVersion): bool
    {
        return $this->invoke(__FUNCTION__, [$publicId, $expectedVersion]);
    }

    public function resetFactors(string $publicId, int $expectedVersion): bool
    {
        return $this->invoke(__FUNCTION__, [$publicId, $expectedVersion]);
    }

    public function trustedDevices(string $publicId, ?string $before = null): array
    {
        return $this->invoke(__FUNCTION__, [$publicId, $before]);
    }

    public function revokeDevice(string $publicId, string $deviceId): bool
    {
        return $this->invoke(__FUNCTION__, [$publicId, $deviceId]);
    }
}
