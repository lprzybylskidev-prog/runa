<?php

namespace Runa\Contracts\Modules;

use Illuminate\Database\Connection;

/** Auth invokes inside its mutation transaction before lifecycle changes. */
interface AuthorityGuard
{
    /** @param list<string> $publicIds UUIDv7 identities in stable sorted order. */
    public function assertMayRemove(Connection $connection, array $publicIds): void;
}
