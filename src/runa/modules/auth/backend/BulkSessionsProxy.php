<?php

namespace Runa\Modules\Auth;

use Runa\Modules\Auth\Contracts\AuthBulkSessions;
use Runa\Modules\Auth\Contracts\SessionSelection;
use Runa\Support\Modules\Api\GuardedApi;

final class BulkSessionsProxy extends GuardedApi implements AuthBulkSessions
{
    public function preview(SessionSelection $selection, bool $trust = false): array
    {
        return $this->invoke(__FUNCTION__, [$selection, $trust]);
    }

    public function revoke(SessionSelection $selection, bool $trust, string $fingerprint): array
    {
        return $this->invoke(__FUNCTION__, [$selection, $trust, $fingerprint]);
    }
}
