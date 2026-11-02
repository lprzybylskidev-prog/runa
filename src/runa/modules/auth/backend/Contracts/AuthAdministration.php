<?php

namespace Runa\Modules\Auth\Contracts;

/** Bounded administrative reads. Resolve through ModuleApis at 1.0.0. */
interface AuthAdministration
{
    public function users(UserDirectoryQuery $query): UserDirectoryPage;

    public function profile(string $publicId): AdministrativeProfile;

    /** Safe account facts, filtered by current feature configuration and field authority.
     * @return array<string,bool|int|string|null> */
    public function lifecycle(string $publicId): array;
}
