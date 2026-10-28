<?php

namespace Runa\Modules\Auth\Contracts;

/** Administrative permission-protected operations. No credentials cross this boundary. */
interface AuthSecurity
{
    public function requestPasswordReset(string $publicId, int $expectedVersion): bool;

    public function resetFactors(string $publicId, int $expectedVersion): bool;

    /**
     * @return list<array{public_id: string, created_at: string, expires_at: string}>
     */
    public function trustedDevices(string $publicId, ?string $before = null): array;

    public function revokeDevice(string $publicId, string $deviceId): bool;
}
