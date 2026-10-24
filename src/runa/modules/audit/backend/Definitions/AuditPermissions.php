<?php

namespace Runa\Modules\Audit\Definitions;

use Runa\Contracts\Modules\ManagementPermission;
use Runa\Contracts\Modules\ManagementProvider;

final class AuditPermissions implements ManagementProvider
{
    public function permissions(): array
    {
        return [
            new ManagementPermission('read', ['pl' => 'Odczyt historii audytu', 'en' => 'Read audit history'], ['pl' => 'Odczyt historii audytu.', 'en' => 'Read audit history.']),
        ];
    }

    public function actions(): array
    {
        return [];
    }

    public function components(): array
    {
        return [];
    }
}
