<?php

namespace Runa\Modules\Admin\Definitions;

use Runa\Contracts\Modules\ManagementPermission;
use Runa\Contracts\Modules\ManagementProvider;

final class AdminPermissions implements ManagementProvider
{
    public function permissions(): array
    {
        return [
            new ManagementPermission('application.read', ['pl' => 'Odczyt ustawień aplikacji', 'en' => 'Read application settings'], ['pl' => 'Odczyt prezentacji aplikacji.', 'en' => 'Read application presentation settings.']),
            new ManagementPermission('application.manage', ['pl' => 'Zmiana ustawień aplikacji', 'en' => 'Manage application settings'], ['pl' => 'Zmiana prezentacji aplikacji.', 'en' => 'Manage application presentation settings.']),
            new ManagementPermission('enter', ['pl' => 'Wejście do administracji', 'en' => 'Enter administration'], ['pl' => 'Wejście do administracji.', 'en' => 'Enter administration.']),
            new ManagementPermission('diagnostics.read', ['pl' => 'Odczyt diagnostyki', 'en' => 'Read diagnostics'], ['pl' => 'Odczyt diagnostyki.', 'en' => 'Read diagnostics.']),
            new ManagementPermission('health.diagnose', ['pl' => 'Diagnostyka modułów', 'en' => 'Diagnose modules'], ['pl' => 'Diagnostyka modułów.', 'en' => 'Diagnose modules.']),
            new ManagementPermission('modules.manage', ['pl' => 'Zarządzanie modułami', 'en' => 'Manage optional modules'], ['pl' => 'Zarządzanie modułami.', 'en' => 'Manage optional modules.']),
            new ManagementPermission('settings.manage', ['pl' => 'Historia i odzyskiwanie ustawień modułów', 'en' => 'Module settings history and recovery'], ['pl' => 'Odczyt historii i odzyskiwanie operacji ustawień.', 'en' => 'Read settings operation history and recover pending operations.']),
            new ManagementPermission('work.read', ['pl' => 'Odczyt operacji', 'en' => 'Read module work'], ['pl' => 'Odczyt operacji.', 'en' => 'Read module work.']),
            new ManagementPermission('work.cancel', ['pl' => 'Anulowanie operacji', 'en' => 'Request work cancellation'], ['pl' => 'Anulowanie operacji.', 'en' => 'Request work cancellation.']),
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
