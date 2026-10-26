<?php

namespace Runa\Support\Modules\Data;

use Illuminate\Database\Connection;
use RuntimeException;

/** Shared Auth/Permissions protocol; all writers acquire this before domain rows. */
final class AuthorityLock
{
    public function acquire(Connection $connection): void
    {
        if ($connection->getDriverName() !== 'pgsql' || $connection->transactionLevel() < 1) {
            throw new RuntimeException('Authority mutation requires an active PostgreSQL transaction.');
        }
        $connection->table('shared.module_registry_state')->where('id', 1)->sharedLock()->first()
            ?? throw new RuntimeException('Module registry unavailable.');
        $connection->select('SELECT pg_advisory_xact_lock(1381322305, 11)');
    }
}
