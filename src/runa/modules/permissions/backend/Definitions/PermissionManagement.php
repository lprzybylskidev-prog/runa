<?php

namespace Runa\Modules\Permissions\Definitions;

use Runa\Contracts\Modules\ManagementPermission;
use Runa\Contracts\Modules\ManagementProvider;

final class PermissionManagement implements ManagementProvider
{
    public function permissions(): array
    {
        return [
            new ManagementPermission('introspection.read', ['pl' => 'Odczyt uprawnień użytkownika', 'en' => 'Inspect user permissions'], ['pl' => 'Odczyt uprawnień użytkownika.', 'en' => 'Inspect user permissions.']),
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
