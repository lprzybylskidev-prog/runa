<?php

namespace Runa\Integration\Core;

use Illuminate\Database\Connection;
use Runa\Contracts\Modules\AuthorityGuard;
use RuntimeException;

final class DenyAuthorityChanges implements AuthorityGuard
{
    public function assertMayRemove(Connection $connection, array $publicIds): void
    {
        throw new RuntimeException('Trusted administrative authority integration unavailable.');
    }
}
