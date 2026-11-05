<?php

namespace Runa\Modules\Auth\Contracts;

/** Administrative permission-protected operations. No credentials cross this boundary. */
interface AuthSecurity
{
    /** False denotes a missing/stale target; IdentityConflict carries a safe business refusal. */
    public function requestPasswordReset(string $publicId, int $expectedVersion): bool;

    public function resetFactors(string $publicId, int $expectedVersion): bool;

    /**
     * @return list<array{public_id: string, browser: ?string, platform: ?string, current: bool, created_at: ?string, last_used_at: ?string, expires_at: string}>
     */
    public function trustedDevices(string $publicId, ?string $before = null): array;

    public function revokeDevices(string $publicId, int $expectedVersion): bool;

    public function revokeDevice(string $publicId, string $deviceId): bool;
}
