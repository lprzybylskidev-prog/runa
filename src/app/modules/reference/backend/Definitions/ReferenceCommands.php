<?php

namespace App\Modules\Reference\Definitions;

use Runa\Contracts\Modules\CommandDefinition;
use Runa\Contracts\Modules\CommandProvider;

final class ReferenceCommands implements CommandProvider
{
    public function commands(): array
    {
        return [new CommandDefinition('inspect', 'diagnostic', ReferenceDiagnostic::class), new CommandDefinition('work', 'operational', 'reference.work')];
    }
}
