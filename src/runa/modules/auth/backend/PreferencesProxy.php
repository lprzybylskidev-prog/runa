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
        return $this->invoke(__FUNCTION__, [$scope]);
    }

    public function write(PreferenceScope $scope, PreferenceValue $value, int $revision): array
    {
        return $this->invoke(__FUNCTION__, [$scope, $value, $revision]);
    }

    public function reset(?string $module, ?PreferenceScope $scope, int $revision): array
    {
        return $this->invoke(__FUNCTION__, [$module, $scope, $revision]);
    }

    public function resetAccount(string $publicId, int $revision): array
    {
        return $this->invoke(__FUNCTION__, [$publicId, $revision]);
    }
}
