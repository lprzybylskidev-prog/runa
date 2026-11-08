<?php

namespace Runa\Contracts\Modules;

use Illuminate\Database\Connection;

/** Optional SettingsProvider extension and required framework persistence; errors roll back publication. */
interface SettingsParticipant
{
    public function publishing(Connection $connection, SettingsPublication $publication): void;
}
