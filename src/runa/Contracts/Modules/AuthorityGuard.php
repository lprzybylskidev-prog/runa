<?php

namespace Runa\Contracts\Modules;

use Illuminate\Database\Connection;

/** Auth invokes inside its mutation transaction before lifecycle changes. */
interface AuthorityGuard
{
    /**
     * Exclude these identities from eligible authority before actual removal.
     * An empty list validates the trusted integration/transaction for non-removing work.
     *
     * @param  list<string>  $publicIds  UUIDv7 identities in stable sorted order.
     */
    public function assertMayRemove(Connection $connection, array $publicIds): void;
}
