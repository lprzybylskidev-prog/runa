<?php

namespace Runa\Modules\Auth;

use Runa\Modules\Auth\Contracts\AuthSecurity;
use Runa\Support\Modules\Api\GuardedApi;

final class SecurityProxy extends GuardedApi implements AuthSecurity
{
    public function requestPasswordReset(string $publicId, int $expectedVersion): bool
    {
        /** @var bool $result */
        $result = $this->invoke(__FUNCTION__, [$publicId, $expectedVersion]);

        return $result;
    }

    public function resetFactors(string $publicId, int $expectedVersion): bool
    {
        /** @var bool $result */
        $result = $this->invoke(__FUNCTION__, [$publicId, $expectedVersion]);

        return $result;
    }

    public function trustedDevices(string $publicId, ?string $before = null): array
    {
        /** @var list<array{public_id: string, created_at: string, expires_at: string}> $result */
        $result = $this->invoke(__FUNCTION__, [$publicId, $before]);

        return $result;
    }

    public function revokeDevice(string $publicId, string $deviceId): bool
    {
        /** @var bool $result */
        $result = $this->invoke(__FUNCTION__, [$publicId, $deviceId]);

        return $result;
    }
}
