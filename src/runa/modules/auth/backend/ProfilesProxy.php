<?php

namespace Runa\Modules\Auth;

use Runa\Modules\Auth\Contracts\AuthProfiles;
use Runa\Modules\Auth\Contracts\Identity;
use Runa\Modules\Auth\Contracts\ProfileChange;
use Runa\Support\Modules\Api\GuardedApi;

final class ProfilesProxy extends GuardedApi implements AuthProfiles
{
    public function edit(string $publicId, int $expectedVersion, ProfileChange $change): Identity
    {
        return $this->invoke(__FUNCTION__, [$publicId, $expectedVersion, $change]);
    }

    public function changeEmail(string $publicId, int $expectedVersion, string $email): bool
    {
        return $this->invoke(__FUNCTION__, [$publicId, $expectedVersion, $email]);
    }

    public function replaceVerifiedEmail(string $publicId, int $expectedVersion, string $email): bool
    {
        return $this->invoke(__FUNCTION__, [$publicId, $expectedVersion, $email]);
    }

    public function requireVerification(string $publicId, int $expectedVersion): bool
    {
        return $this->invoke(__FUNCTION__, [$publicId, $expectedVersion]);
    }

    public function addSecondary(string $publicId, int $expectedVersion, string $email): string
    {
        return $this->invoke(__FUNCTION__, [$publicId, $expectedVersion, $email]);
    }

    public function changeSecondary(string $publicId, int $expectedVersion, string $addressId, string $email): string
    {
        return $this->invoke(__FUNCTION__, [$publicId, $expectedVersion, $addressId, $email]);
    }

    public function removeSecondary(string $publicId, int $expectedVersion, string $addressId): bool
    {
        return $this->invoke(__FUNCTION__, [$publicId, $expectedVersion, $addressId]);
    }

    public function notificationEmails(string $publicId): array
    {
        return $this->invoke(__FUNCTION__, [$publicId]);
    }
}
