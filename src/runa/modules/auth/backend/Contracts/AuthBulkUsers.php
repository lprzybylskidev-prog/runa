<?php

namespace Runa\Modules\Auth\Contracts;

interface AuthBulkUsers
{
    /** @return array{fingerprint:string,accounts:int,affected_accounts:int,sessions:int,trusted_devices:int,executable:bool,refusal_reason:?string} */
    public function preview(SessionSelection $selection, BulkUserChange $change): array;

    /** @return array{accounts:int} */
    public function execute(SessionSelection $selection, BulkUserChange $change, string $fingerprint): array;
}
