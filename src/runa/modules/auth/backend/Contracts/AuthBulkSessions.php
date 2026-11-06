<?php

namespace Runa\Modules\Auth\Contracts;

interface AuthBulkSessions
{
    /** Selected UUIDv7 identities; all-user selection is resolved by Permissions.
     *
     * @return array{accounts: list<array{public_id: string, version: int, security_version: int, sessions: list<string>, trusted_devices: list<string>}>, sessions: int, trusted_devices: int, fingerprint: string, selected_accounts: int, skipped_accounts: int}
     */
    public function preview(SessionSelection $selection, bool $trust = false): array;

    /**
     * @return array{accounts: int, sessions: int, trusted_devices: int, skipped_accounts: int}
     */
    public function revoke(SessionSelection $selection, bool $trust, string $fingerprint): array;
}
