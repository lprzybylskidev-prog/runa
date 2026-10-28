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
        /** @var Identity $result */
        $result = $this->invoke(__FUNCTION__, [$publicId, $expectedVersion, $change]);

        return $result;
    }

    public function changeEmail(string $publicId, int $expectedVersion, string $email): bool
    {
        /** @var bool $result */
        $result = $this->invoke(__FUNCTION__, [$publicId, $expectedVersion, $email]);

        return $result;
    }

    public function replaceVerifiedEmail(string $publicId, int $expectedVersion, string $email): bool
    {
        /** @var bool $result */
        $result = $this->invoke(__FUNCTION__, [$publicId, $expectedVersion, $email]);

        return $result;
    }

    public function requireVerification(string $publicId, int $expectedVersion): bool
    {
        /** @var bool $result */
        $result = $this->invoke(__FUNCTION__, [$publicId, $expectedVersion]);

        return $result;
    }

    public function addSecondary(string $publicId, int $expectedVersion, string $email): string
    {
        /** @var string $result */
        $result = $this->invoke(__FUNCTION__, [$publicId, $expectedVersion, $email]);

        return $result;
    }

    public function changeSecondary(string $publicId, int $expectedVersion, string $addressId, string $email): string
    {
        /** @var string $result */
        $result = $this->invoke(__FUNCTION__, [$publicId, $expectedVersion, $addressId, $email]);

        return $result;
    }

    public function removeSecondary(string $publicId, int $expectedVersion, string $addressId): bool
    {
        /** @var bool $result */
        $result = $this->invoke(__FUNCTION__, [$publicId, $expectedVersion, $addressId]);

        return $result;
    }

    public function notificationEmails(string $publicId): array
    {
        /** @var list<string> $result */
        $result = $this->invoke(__FUNCTION__, [$publicId]);

        return $result;
    }
}
