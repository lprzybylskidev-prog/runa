<?php

namespace Runa\Modules\Auth;

use Runa\Modules\Auth\Contracts\AuthBulkSessions;
use Runa\Modules\Auth\Contracts\SessionSelection;
use Runa\Support\Modules\Api\GuardedApi;

final class BulkSessionsProxy extends GuardedApi implements AuthBulkSessions
{
    public function preview(SessionSelection $selection, bool $trust = false): array
    {
        /** @var array{accounts: list<array{public_id: string, version: int, security_version: int, sessions: list<string>, trusted_devices: list<string>}>, sessions: int, trusted_devices: int, fingerprint: string} $result */
        $result = $this->invoke(__FUNCTION__, [$selection, $trust]);

        return $result;
    }

    public function revoke(SessionSelection $selection, bool $trust, string $fingerprint): array
    {
        /** @var array{accounts: int, sessions: int, trusted_devices: int} $result */
        $result = $this->invoke(__FUNCTION__, [$selection, $trust, $fingerprint]);

        return $result;
    }
}
