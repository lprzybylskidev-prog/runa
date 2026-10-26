<?php

namespace Runa\Modules\Auth\Contracts;

interface AuthPreferences
{
    public function read(PreferenceScope $scope): array;

    public function write(PreferenceScope $scope, PreferenceValue $value, int $revision): array;

    /** Null module/key means reset all/module. Revision belongs to the account. */
    public function reset(?string $module, ?PreferenceScope $scope, int $revision): array;

    public function resetAccount(string $publicId, int $revision): array;
}
