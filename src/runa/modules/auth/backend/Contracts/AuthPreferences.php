<?php

namespace Runa\Modules\Auth\Contracts;

interface AuthPreferences
{
    /** @return array{account: string, revision: int, scope: string, version: int, value: mixed, stored: bool, legacy_claimed: bool} */
    public function read(PreferenceScope $scope): array;

    /** @return array{account: string, revision: int, scope: string, version: int, value: mixed, stored: bool, legacy_claimed: bool} */
    public function write(PreferenceScope $scope, PreferenceValue $value, int $revision): array;

    /** Null module/key means reset all/module. Revision belongs to the account.
     * @return array{account: string, revision: int}
     */
    public function reset(?string $module, ?PreferenceScope $scope, int $revision): array;

    /** @return array{account: string, revision: int} */
    public function resetAccount(string $publicId, int $revision): array;
}
