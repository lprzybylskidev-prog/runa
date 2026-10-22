<?php

namespace App\Modules\ModuleProbe\Definitions;

use Runa\Contracts\Modules\ManagementAction;
use Runa\Contracts\Modules\ManagementPermission;
use Runa\Contracts\Modules\ManagementProvider;

final class ProbeManagement implements ManagementProvider
{
    public function permissions(): array
    {
        return [new ManagementPermission('probe.inspect', ['pl' => 'Inspekcja sondy', 'en' => 'Inspect probe'], ['pl' => 'Odczyt diagnostyki sondy.', 'en' => 'Read probe diagnostics.'])];
    }

    public function actions(): array
    {
        return [new ManagementAction('inspect', 'probe.inspect', ['pl' => 'Sprawdź sondę', 'en' => 'Inspect probe'], ['pl' => 'Opis przyszłej akcji administracyjnej.', 'en' => 'Descriptor for a future administrative action.'])];
    }

    public function components(): array
    {
        return [];
    }
}
