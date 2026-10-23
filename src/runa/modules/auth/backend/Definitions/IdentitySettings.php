<?php

namespace Runa\Modules\Auth\Definitions;

use Runa\Contracts\Modules\SettingDefinition;
use Runa\Contracts\Modules\SettingsContext;
use Runa\Contracts\Modules\SettingsHandler;
use Runa\Contracts\Modules\SettingsProvider;

/** Immediate lookup/future-deletion policy. Existing ownership is never reassigned. */
final class IdentitySettings implements SettingsHandler, SettingsProvider
{
    public function settings(): array
    {
        return [
            new SettingDefinition('public_registration', 'boolean', false, ['pl' => 'Rejestracja publiczna', 'en' => 'Public registration'], ['pl' => 'Zezwala na samodzielne utworzenie konta.', 'en' => 'Allow public account creation.']),
            new SettingDefinition('registration_approval', 'string', 'automatic', ['pl' => 'Zatwierdzanie rejestracji', 'en' => 'Registration approval'], ['pl' => 'Automatycznie lub przez administratora.', 'en' => 'Automatic or administrator approval.'], rules: ['in:automatic,administrator']),
            new SettingDefinition('invitation_minutes', 'integer', 1440, ['pl' => 'Ważność zaproszenia', 'en' => 'Invitation lifetime'], ['pl' => 'Czas ważności w minutach.', 'en' => 'Token lifetime in minutes.'], rules: ['min:5', 'max:10080']),
            new SettingDefinition('verification_minutes', 'integer', 60, ['pl' => 'Ważność weryfikacji', 'en' => 'Verification lifetime'], ['pl' => 'Czas ważności w minutach.', 'en' => 'Token lifetime in minutes.'], rules: ['min:5', 'max:1440']),
            new SettingDefinition('invitation_reminder_minutes', 'integer', 0, ['pl' => 'Odstęp przypomnień', 'en' => 'Reminder interval'], ['pl' => 'Minuty; zero wyłącza przypomnienia.', 'en' => 'Minutes; zero disables reminders.'], rules: ['min:0', 'max:10080']),
            new SettingDefinition('invitation_reminder_count', 'integer', 0, ['pl' => 'Limit przypomnień', 'en' => 'Reminder limit'], ['pl' => 'Maksymalna liczba przypomnień.', 'en' => 'Maximum number of invitation reminders.'], rules: ['min:0', 'max:10']),
            new SettingDefinition('identifier_mode', 'string', 'email', ['pl' => 'Identyfikator logowania', 'en' => 'Login identifier'],
                ['pl' => 'E-mail, login lub oba identyfikatory.', 'en' => 'Email, login or either identifier.'], rules: ['in:email,login,either']),
            new SettingDefinition('reuse_deleted_identifiers', 'boolean', false, ['pl' => 'Ponowne użycie identyfikatorów', 'en' => 'Reuse deleted identifiers'],
                ['pl' => 'Zwalnia identyfikatory przy kolejnych usunięciach; nie przenosi historii.', 'en' => 'Release identifiers on subsequent deletions; never transfer history.']),
        ];
    }

    public function valid(array $values): bool
    {
        return true;
    }

    public function handler(): string
    {
        return self::class;
    }

    public function apply(SettingsContext $context): bool
    {
        return true;
    }

    public function compensate(SettingsContext $context): bool
    {
        return true;
    }
}
