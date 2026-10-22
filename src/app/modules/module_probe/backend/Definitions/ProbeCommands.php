<?php

namespace App\Modules\ModuleProbe\Definitions;

use Runa\Contracts\Modules\CommandDefinition;
use Runa\Contracts\Modules\CommandProvider;

final class ProbeCommands implements CommandProvider
{
    public function commands(): array
    {
        return [new CommandDefinition('inspect', 'diagnostic', ProbeDiagnostic::class), new CommandDefinition('work', 'operational', 'probe.work')];
    }
}
