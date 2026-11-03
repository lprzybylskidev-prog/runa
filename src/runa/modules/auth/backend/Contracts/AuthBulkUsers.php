<?php

namespace Runa\Modules\Auth\Contracts;

interface AuthBulkUsers
{
    /** @return array{fingerprint:string,accounts:int,affected_accounts:int,sessions:int,trusted_devices:int,skipped_accounts:int} */
    public function preview(SessionSelection $selection, BulkUserChange $change): array;

    /** @return array{accounts:int,skipped_accounts:int} */
    public function execute(SessionSelection $selection, BulkUserChange $change, string $fingerprint): array;
}
