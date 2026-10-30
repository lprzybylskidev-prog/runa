<?php

namespace Runa\Modules\Auth\Contracts;

/** Bounded administrative reads. Resolve through ModuleApis at 1.0.0. */
interface AuthAdministration
{
    public function users(UserDirectoryQuery $query): UserDirectoryPage;

    public function profile(string $publicId): AdministrativeProfile;

    /** @return array{public_id:string,version:int,active:bool,approved:bool,deleted:bool,security_locked:bool,password_change_required:bool,activate_at:?string,deactivate_at:?string,expires_at:?string,attempt_locked_until:?string} */
    public function lifecycle(string $publicId): array;
}
