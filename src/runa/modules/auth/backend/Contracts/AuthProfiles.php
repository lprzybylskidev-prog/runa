<?php

namespace Runa\Modules\Auth\Contracts;

interface AuthProfiles
{
    public function edit(string $publicId, int $expectedVersion, ProfileChange $change): Identity;

    public function changeEmail(string $publicId, int $expectedVersion, string $email): bool;

    public function replaceVerifiedEmail(string $publicId, int $expectedVersion, string $email): bool;

    public function requireVerification(string $publicId, int $expectedVersion): bool;

    public function addSecondary(string $publicId, int $expectedVersion, string $email): string;

    public function changeSecondary(string $publicId, int $expectedVersion, string $addressId, string $email): string;

    public function removeSecondary(string $publicId, int $expectedVersion, string $addressId): bool;

    /** Verified, notification-only recipients; primary security routing is never taken from this list. */
    public function notificationEmails(string $publicId): array;
}
