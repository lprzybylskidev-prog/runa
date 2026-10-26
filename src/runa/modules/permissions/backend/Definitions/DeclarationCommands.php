<?php

namespace Runa\Modules\Permissions\Definitions;

use Runa\Contracts\Modules\CommandDefinition;
use Runa\Contracts\Modules\CommandProvider;

final class DeclarationCommands implements CommandProvider
{
    public function commands(): array
    {
        return [new CommandDefinition('declarations', 'diagnostic', DeclarationDiagnostic::class)];
    }
}
