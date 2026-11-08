<?php

namespace Runa\Contracts\Modules;

use Illuminate\Database\Connection;

/** Optional SettingsProvider extension: mandatory owning authorization before publication/fencing. */
interface SettingsAuthorization
{
    public function authorizing(Connection $connection, SettingsContext $context): void;
}
