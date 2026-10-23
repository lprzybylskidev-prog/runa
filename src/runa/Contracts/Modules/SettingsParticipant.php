<?php

namespace Runa\Contracts\Modules;

use Illuminate\Database\Connection;

/** Mandatory synchronous persistence; exceptions roll back the publication. */
interface SettingsParticipant
{
    public function publishing(Connection $connection, SettingsPublication $publication): void;
}
