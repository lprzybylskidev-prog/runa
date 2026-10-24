<?php

namespace Runa\Contracts\Modules;

use Illuminate\Database\Connection;

/** Optional mandatory authorization before publication/fencing, in the same transaction. */
interface SettingsAuthorization
{
    public function authorizing(Connection $connection, SettingsContext $context): void;
}
