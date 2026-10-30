<?php

namespace Runa\Modules\Admin\Definitions;

use Runa\Contracts\Modules\ManagementPermission;
use Runa\Contracts\Modules\ManagementProvider;

final class AdminPermissions implements ManagementProvider
{
    public function permissions(): array
    {
        return [
            new ManagementPermission('enter', ['pl' => 'Wejście do administracji', 'en' => 'Enter administration'], ['pl' => 'Wejście do administracji.', 'en' => 'Enter administration.']),
            new ManagementPermission('diagnostics.read', ['pl' => 'Odczyt diagnostyki', 'en' => 'Read diagnostics'], ['pl' => 'Odczyt diagnostyki.', 'en' => 'Read diagnostics.']),
            new ManagementPermission('health.diagnose', ['pl' => 'Diagnostyka modułów', 'en' => 'Diagnose modules'], ['pl' => 'Diagnostyka modułów.', 'en' => 'Diagnose modules.']),
            new ManagementPermission('health.configure', ['pl' => 'Konfiguracja diagnostyki', 'en' => 'Configure health policy'], ['pl' => 'Konfiguracja diagnostyki.', 'en' => 'Configure health policy.']),
            new ManagementPermission('modules.manage', ['pl' => 'Zarządzanie modułami', 'en' => 'Manage optional modules'], ['pl' => 'Zarządzanie modułami.', 'en' => 'Manage optional modules.']),
            new ManagementPermission('settings.read', ['pl' => 'Odczyt ustawień modułów', 'en' => 'Read module settings'], ['pl' => 'Odczyt ustawień modułów.', 'en' => 'Read module settings.']),
            new ManagementPermission('settings.manage', ['pl' => 'Zmiana ustawień modułów', 'en' => 'Manage module settings'], ['pl' => 'Zmiana ustawień modułów.', 'en' => 'Manage module settings.']),
            new ManagementPermission('integrations.manage', ['pl' => 'Konfiguracja integracji', 'en' => 'Configure integrations'], ['pl' => 'Konfiguracja integracji.', 'en' => 'Configure integrations.']),
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
