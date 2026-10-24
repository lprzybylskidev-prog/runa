<?php

namespace Runa\Modules\Auth\Contracts;

interface AuthBulkSessions
{
    /** Selected UUIDv7 identities; all-user selection is resolved by Permissions. */
    public function preview(SessionSelection $selection, bool $trust = false): array;

    public function revoke(SessionSelection $selection, bool $trust, string $fingerprint): array;
}
