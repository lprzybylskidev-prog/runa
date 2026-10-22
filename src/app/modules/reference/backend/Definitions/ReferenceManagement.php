<?php

namespace App\Modules\Reference\Definitions;

use Runa\Contracts\Modules\ManagementAction;
use Runa\Contracts\Modules\ManagementPermission;
use Runa\Contracts\Modules\ManagementProvider;

final class ReferenceManagement implements ManagementProvider
{
    public function permissions(): array
    {
        return [new ManagementPermission('reference.inspect', ['pl' => 'Inspekcja przykładu', 'en' => 'Inspect reference'], ['pl' => 'Odczyt diagnostyki przykładu.', 'en' => 'Read reference diagnostics.'])];
    }

    public function actions(): array
    {
        return [new ManagementAction('inspect', 'reference.inspect', ['pl' => 'Sprawdź przykład', 'en' => 'Inspect reference'], ['pl' => 'Opis przyszłej akcji administracyjnej.', 'en' => 'Descriptor for a future administrative action.'])];
    }

    public function components(): array
    {
        return [];
    }
}
