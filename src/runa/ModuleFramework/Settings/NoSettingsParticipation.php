<?php

namespace Runa\ModuleFramework\Settings;

use Illuminate\Database\Connection;
use Runa\Contracts\Modules\SettingsParticipant;
use Runa\Contracts\Modules\SettingsPublication;

/** Baseline only; Audit/Auth supply required persistence when installed. */
final class NoSettingsParticipation implements SettingsParticipant
{
    public function publishing(Connection $connection, SettingsPublication $publication): void
    {
        if ($connection->table('shared.modules')->where('module_id', 'runa.audit')->whereNotNull('installed_version')->exists()) {
            throw new \RuntimeException('Installed Audit requires transactional participation.');
        }
    }
}
