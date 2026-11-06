<?php

namespace Runa\Modules\Auth\Contracts;

/**
 * @phpstan-type AdministrativeDelivery array{module:string,key:string,names:array{pl:string,en:string},mandatory:bool,sensitive:bool,channel:string,enabled:bool,recipients:list<string>}
 * @phpstan-type AdministrativePreferences array{account:string,revision:int,locale:string,theme:string,categories:list<AdministrativeDelivery>,recipients:list<array{value:string,label:string}>}
 */
interface AuthPreferences
{
    /** Administrative snapshot for a selected account; never changes the current principal.
     * @return AdministrativePreferences
     */
    public function account(string $publicId): array;

    /** @param list<array{module:string,key:string,enabled:bool,recipients:list<string>}> $delivery
     * @return array{account:string,revision:int}
     */
    public function updateAccount(string $publicId, string $locale, string $theme, array $delivery, int $revision): array;

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
