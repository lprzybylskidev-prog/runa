<?php

namespace Runa\Modules\Auth\Definitions;

use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\ManagementEntity;
use Runa\Contracts\Modules\ManagementEntityProvider;
use Runa\Contracts\Modules\ManagementField;
use Runa\Contracts\Modules\ManagementOperation;
use Runa\Contracts\Modules\ManagementPermission;

final class AuthPermissions implements ManagementEntityProvider
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

    public function entities(): array
    {
        $field = static fn (string $key, string $type, bool $write = false, bool $filter = false, bool $sort = false) => new ManagementField($key, $type, ['pl' => $key, 'en' => $key], ['pl' => 'Pole konta; walidacja po stronie Auth.', 'en' => 'Account field; Auth validates all mutations.'], filterable: $filter, sortable: $sort, writable: $write);

        return [
            new ManagementEntity('user', 'identities.read', ['pl' => 'Konta', 'en' => 'Accounts'],
                ['pl' => 'Prywatne dane i stan konta; bez hasła, tokenów i sekretów.', 'en' => 'Private identity and lifecycle; credentials, tokens and factor secrets are excluded.'], [
                    $field('public_id', 'uuid', filter: true), $field('version', 'integer'),
                    new ManagementField('name', 'text', ['pl' => 'Nazwa', 'en' => 'Name'], ['pl' => 'Nazwa wyświetlana.', 'en' => 'Display name.'], writable: true, required: true, maxLength: 160),
                    new ManagementField('email', 'text', ['pl' => 'Adres główny', 'en' => 'Primary email'], ['pl' => 'Nowe konto; zmiana przez osobną operację.', 'en' => 'New identity only; changes require the dedicated email operation.'], writable: true, sensitive: true, required: true, maxLength: 254),
                    $field('active', 'boolean', true, true), $field('approved', 'boolean', true, true),
                    new ManagementField('operation', 'dictionary', ['pl' => 'Operacja', 'en' => 'Operation'], ['pl' => 'Jawna zmiana cyklu życia.', 'en' => 'Explicit lifecycle transition.'], writable: true, choices: ['activate', 'deactivate', 'approve', 'delete', 'restore', 'schedule_activation', 'schedule_deactivation', 'set_expiry', 'set_attempt_lock', 'set_security_lock', 'require_password_change']),
                    $field('at', 'datetime', true), $field('enabled', 'boolean', true),
                ], [
                    new ManagementOperation(new ApiOperation('identity.create', 'users.create'), ['name', 'email', 'active', 'approved'], revisionRequired: false),
                    new ManagementOperation(new ApiOperation('identity.change', 'users.manage'), ['operation', 'at', 'enabled']),
                ]),
            new ManagementEntity('session', 'sessions.manage', ['pl' => 'Sesje', 'en' => 'Sessions'],
                ['pl' => 'Jawny wybór Permissions i atomowe unieważnienie; limit 100 kont.', 'en' => 'Explicit Permissions selection and atomic revocation; at most 100 accounts.'], [
                    new ManagementField('user_public_id', 'uuid', ['pl' => 'Konta', 'en' => 'Accounts'], ['pl' => 'Wybrane konta lub jawny tryb all.', 'en' => 'Selected accounts or explicit all mode.'], writable: true, referenceModule: 'runa.auth', referenceOperation: 'identity.read', multiple: true),
                    $field('all', 'boolean', true), $field('trust', 'boolean', true),
                ], [new ManagementOperation(new ApiOperation('sessions.bulk_revoke', 'sessions.bulk_revoke'), ['user_public_id', 'all', 'trust'], 'both', previewRequired: true, bulk: true)]),
            new ManagementEntity('policy', 'policy.read', ['pl' => 'Polityka', 'en' => 'Policy'],
                ['pl' => 'Bieżące ustawienia modułu i niezmienna historia; podgląd przed publikacją.', 'en' => 'Current ModuleSettings and immutable history; preview before publication.'], [
                    $field('revision', 'integer', true), $field('generation', 'integer', true),
                ], [new ManagementOperation(new ApiOperation('policy.publish', 'policy.write'), ['revision', 'generation'], 'both', previewRequired: true),
                    new ManagementOperation(new ApiOperation('policy.restore', 'policy.write'), ['revision', 'generation'], 'both', previewRequired: true)]),
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
