<?php

namespace Runa\Modules\Auth\Definitions;

use Runa\Contracts\Modules\ManagementPermission;
use Runa\Contracts\Modules\ManagementProvider;

final class AuthPermissions implements ManagementProvider
{
    public function permissions(): array
    {
        return [
            new ManagementPermission('trust.manage', ['pl' => 'Zarządzanie zaufaniem', 'en' => 'Manage trusted devices'], ['pl' => 'Zarządzanie zaufanymi urządzeniami.', 'en' => 'Manage trusted devices.']),
            new ManagementPermission('secondary.manage', ['pl' => 'Zarządzanie adresami dodatkowymi', 'en' => 'Manage secondary emails'], ['pl' => 'Zarządzanie dodatkowymi adresami.', 'en' => 'Manage secondary emails.']),
            new ManagementPermission('avatars.manage', ['pl' => 'Zarządzanie awatarami', 'en' => 'Manage avatars'], ['pl' => 'Zarządzanie awatarami.', 'en' => 'Manage avatars.']),
            new ManagementPermission('identities.read', ['pl' => 'Odczyt tożsamości', 'en' => 'Read identities'], ['pl' => 'Odczyt tożsamości.', 'en' => 'Read identities.']),
            new ManagementPermission('eligibility.read', ['pl' => 'Odczyt dostępności konta', 'en' => 'Read account eligibility'], ['pl' => 'Odczyt dostępności konta.', 'en' => 'Read account eligibility.']),
            new ManagementPermission('users.create', ['pl' => 'Tworzenie kont', 'en' => 'Create accounts'], ['pl' => 'Tworzenie kont.', 'en' => 'Create accounts.']),
            new ManagementPermission('users.manage', ['pl' => 'Zarządzanie kontami', 'en' => 'Manage accounts'], ['pl' => 'Zarządzanie kontami.', 'en' => 'Manage accounts.']),
            new ManagementPermission('invitations.manage', ['pl' => 'Zarządzanie zaproszeniami', 'en' => 'Manage invitations'], ['pl' => 'Zarządzanie zaproszeniami.', 'en' => 'Manage invitations.']),
            new ManagementPermission('sessions.manage', ['pl' => 'Zarządzanie sesjami', 'en' => 'Manage sessions'], ['pl' => 'Zarządzanie sesjami.', 'en' => 'Manage sessions.']),
            new ManagementPermission('sessions.bulk_revoke', ['pl' => 'Zbiorowe unieważnianie sesji', 'en' => 'Bulk revoke sessions'], ['pl' => 'Zbiorowe unieważnianie sesji.', 'en' => 'Bulk revoke sessions.']),
            new ManagementPermission('passwords.reset', ['pl' => 'Resetowanie haseł', 'en' => 'Reset passwords'], ['pl' => 'Resetowanie haseł.', 'en' => 'Reset passwords.']),
            new ManagementPermission('two_factor.reset', ['pl' => 'Resetowanie drugiego składnika', 'en' => 'Reset second factors'], ['pl' => 'Resetowanie drugiego składnika.', 'en' => 'Reset second factors.']),
            new ManagementPermission('profiles.manage', ['pl' => 'Zarządzanie profilami', 'en' => 'Manage profiles'], ['pl' => 'Zarządzanie profilami.', 'en' => 'Manage profiles.']),
            new ManagementPermission('emails.manage', ['pl' => 'Zmiana głównego adresu', 'en' => 'Change primary email'], ['pl' => 'Zmiana głównego adresu.', 'en' => 'Change primary email.']),
            new ManagementPermission('emails.verify_bypass', ['pl' => 'Pominięcie weryfikacji adresu', 'en' => 'Bypass email verification'], ['pl' => 'Pominięcie weryfikacji adresu.', 'en' => 'Bypass email verification.']),
            new ManagementPermission('emails.reverify', ['pl' => 'Wymaganie weryfikacji adresu', 'en' => 'Require email verification'], ['pl' => 'Wymaganie weryfikacji adresu.', 'en' => 'Require email verification.']),
            new ManagementPermission('addresses.read', ['pl' => 'Odczyt adresów odbiorców', 'en' => 'Read recipient addresses'], ['pl' => 'Odczyt adresów odbiorców.', 'en' => 'Read recipient addresses.']),
            new ManagementPermission('delivery.resolve', ['pl' => 'Rozstrzyganie dostarczania', 'en' => 'Resolve delivery'], ['pl' => 'Rozstrzyganie dostarczania.', 'en' => 'Resolve delivery.']),
            new ManagementPermission('preferences.reset', ['pl' => 'Resetowanie preferencji', 'en' => 'Reset preferences'], ['pl' => 'Resetowanie preferencji.', 'en' => 'Reset preferences.']),
            new ManagementPermission('policy.read', ['pl' => 'Odczyt polityki', 'en' => 'Read policy'], ['pl' => 'Odczyt polityki.', 'en' => 'Read policy.']),
            new ManagementPermission('policy.write', ['pl' => 'Zmiana polityki', 'en' => 'Write policy'], ['pl' => 'Zmiana polityki.', 'en' => 'Write policy.']),
            new ManagementPermission('policy.exceptions', ['pl' => 'Zarządzanie wyjątkami polityki', 'en' => 'Manage policy exceptions'], ['pl' => 'Zarządzanie wyjątkami polityki.', 'en' => 'Manage policy exceptions.']),
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
