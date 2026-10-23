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
            new SettingDefinition('session_limit', 'integer', 0, ['pl' => 'Limit równoczesnych sesji', 'en' => 'Concurrent session limit'], ['pl' => 'Limit równoczesnych sesji.', 'en' => 'Concurrent session limit.'], rules: ['min:0', 'max:100']),
            new SettingDefinition('session_idle_minutes', 'integer', 120, ['pl' => 'Limit bezczynności sesji', 'en' => 'Session inactivity timeout'], ['pl' => 'Limit bezczynności sesji.', 'en' => 'Session inactivity timeout.'], rules: ['min:1', 'max:43200']),
            new SettingDefinition('session_absolute_minutes', 'integer', 720, ['pl' => 'Bezwzględna ważność sesji', 'en' => 'Absolute session lifetime'], ['pl' => 'Bezwzględna ważność sesji.', 'en' => 'Absolute session lifetime.'], rules: ['min:1', 'max:43200']),
            new SettingDefinition('session_remember_minutes', 'integer', 0, ['pl' => 'Ważność zapamiętanej sesji', 'en' => 'Remembered session lifetime'], ['pl' => 'Ważność zapamiętanej sesji.', 'en' => 'Remembered session lifetime.'], rules: ['min:0', 'max:43200']),
            new SettingDefinition('session_heartbeat_seconds', 'integer', 30, ['pl' => 'Odstęp sygnałów aktywności', 'en' => 'Activity heartbeat interval'], ['pl' => 'Odstęp sygnałów aktywności.', 'en' => 'Activity heartbeat interval.'], rules: ['min:10', 'max:300']),
            new SettingDefinition('session_warning_seconds', 'integer', 120, ['pl' => 'Wyprzedzenie ostrzeżenia', 'en' => 'Expiry warning interval'], ['pl' => 'Wyprzedzenie ostrzeżenia.', 'en' => 'Expiry warning interval.'], rules: ['min:0', 'max:3600']),
            new SettingDefinition('abuse_window_seconds', 'integer', 300, ['pl' => 'Okno zliczania prób', 'en' => 'Login counting window'], ['pl' => 'Okno zliczania prób.', 'en' => 'Login counting window.'], rules: ['min:30', 'max:3600']),
            new SettingDefinition('abuse_account_limit', 'integer', 10, ['pl' => 'Limit nieudanych prób konta', 'en' => 'Account failure limit'], ['pl' => 'Limit nieudanych prób konta.', 'en' => 'Account failure limit.'], rules: ['min:1', 'max:1000']),
            new SettingDefinition('abuse_source_limit', 'integer', 60, ['pl' => 'Limit prób źródła', 'en' => 'Source attempt limit'], ['pl' => 'Limit prób źródła.', 'en' => 'Source attempt limit.'], rules: ['min:1', 'max:10000']),
            new SettingDefinition('abuse_lock_seconds', 'integer', 300, ['pl' => 'Czas czasowej blokady logowania', 'en' => 'Temporary login lock duration'], ['pl' => 'Czas czasowej blokady logowania.', 'en' => 'Temporary login lock duration.'], rules: ['min:30', 'max:3600']),
            new SettingDefinition('abuse_escalation_limit', 'integer', 0, ['pl' => 'Próg eskalacji nieudanych prób', 'en' => 'Failure escalation threshold'], ['pl' => 'Próg eskalacji nieudanych prób.', 'en' => 'Failure escalation threshold.'], rules: ['min:0', 'max:1000']),
            new SettingDefinition('session_extension', 'boolean', true, ['pl' => 'Przedłużanie aktywnością', 'en' => 'Activity extension'], ['pl' => 'Rzeczywista aktywność odświeża czas bezczynności.', 'en' => 'Real activity renews inactivity timeout.']),
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
