<?php

namespace Runa\Modules\Auth\Contracts;

interface AuthSessions
{
    /** @return list<array{public_id: string, current: bool, remembered: bool, authenticated_at: string, activity_at: string, expires_at: string}> */
    public function listFor(string $publicId, ?string $before = null): array;

    public function revoke(string $publicId, string $sessionId): bool;

    public function revokeAll(string $publicId): bool;
}
