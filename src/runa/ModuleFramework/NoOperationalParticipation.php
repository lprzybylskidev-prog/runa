<?php

namespace Runa\ModuleFramework;

use Illuminate\Database\Connection;
use Runa\Contracts\Modules\OperationalParticipant;

/** Standalone framework installations have no Audit Core. */
final class NoOperationalParticipation implements OperationalParticipant
{
    public function recording(Connection $connection, string $module, string $operation, array $context): void {}
}
