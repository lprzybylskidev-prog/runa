<?php

namespace Runa\Modules\Auth;

use Runa\Modules\Auth\Contracts\AuthPreferences;
use Runa\Modules\Auth\Contracts\PreferenceScope;
use Runa\Modules\Auth\Contracts\PreferenceValue;
use Runa\Support\Modules\Api\GuardedApi;

final class PreferencesProxy extends GuardedApi implements AuthPreferences
{
    public function read(PreferenceScope $scope): array
    {
        /** @var array{account: string, revision: int, scope: string, version: int, value: mixed, stored: bool, legacy_claimed: bool} $result */
        $result = $this->invoke(__FUNCTION__, [$scope]);

        return $result;
    }

    public function write(PreferenceScope $scope, PreferenceValue $value, int $revision): array
    {
        /** @var array{account: string, revision: int, scope: string, version: int, value: mixed, stored: bool, legacy_claimed: bool} $result */
        $result = $this->invoke(__FUNCTION__, [$scope, $value, $revision]);

        return $result;
    }

    public function reset(?string $module, ?PreferenceScope $scope, int $revision): array
    {
        /** @var array{account: string, revision: int} $result */
        $result = $this->invoke(__FUNCTION__, [$module, $scope, $revision]);

        return $result;
    }

    public function resetAccount(string $publicId, int $revision): array
    {
        /** @var array{account: string, revision: int} $result */
        $result = $this->invoke(__FUNCTION__, [$publicId, $revision]);

        return $result;
    }
}
