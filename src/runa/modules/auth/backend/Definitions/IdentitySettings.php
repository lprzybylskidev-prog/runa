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
            new SettingDefinition('own_name_editable', 'boolean', true, ['pl' => 'Samodzielna edycja imienia i nazwiska', 'en' => 'Own name editable'], ['pl' => 'Samodzielna edycja imienia i nazwiska.', 'en' => 'Own name editable.']),
            new SettingDefinition('own_login_editable', 'boolean', true, ['pl' => 'Samodzielna edycja loginu', 'en' => 'Own login editable'], ['pl' => 'Samodzielna edycja loginu.', 'en' => 'Own login editable.']),
            new SettingDefinition('own_email_editable', 'boolean', true, ['pl' => 'Samodzielna zmiana adresu e-mail', 'en' => 'Own email editable'], ['pl' => 'Samodzielna zmiana adresu e-mail.', 'en' => 'Own email editable.']),
            new SettingDefinition('email_change_old_confirmation', 'boolean', false, ['pl' => 'Potwierdzenie przez poprzedni adres', 'en' => 'Email change old confirmation'], ['pl' => 'Potwierdzenie przez poprzedni adres.', 'en' => 'Email change old confirmation.']),
            new SettingDefinition('email_change_minutes', 'integer', 60, ['pl' => 'Ważność zmiany adresu e-mail', 'en' => 'Email change minutes'], ['pl' => 'Ważność zmiany adresu e-mail.', 'en' => 'Email change minutes.'], rules: ['min:5', 'max:1440']),
            new SettingDefinition('email_change_session_policy', 'string', 'revoke_others', ['pl' => 'Sesje po zmianie adresu e-mail', 'en' => 'Email change session policy'], ['pl' => 'Sesje po zmianie adresu e-mail.', 'en' => 'Email change session policy.'], rules: ['in:keep_all,revoke_others,revoke_all']),
            new SettingDefinition('admin_email_verification_bypass', 'boolean', false, ['pl' => 'Administracyjne pominięcie weryfikacji adresu', 'en' => 'Admin email verification bypass'], ['pl' => 'Administracyjne pominięcie weryfikacji adresu.', 'en' => 'Admin email verification bypass.']),
            new SettingDefinition('secondary_emails_enabled', 'boolean', false, ['pl' => 'Dodatkowe adresy e-mail', 'en' => 'Secondary emails enabled'], ['pl' => 'Dodatkowe adresy e-mail.', 'en' => 'Secondary emails enabled.']),
            new SettingDefinition('secondary_self_add', 'boolean', true, ['pl' => 'Samodzielne dodawanie adresów', 'en' => 'Secondary self add'], ['pl' => 'Samodzielne dodawanie adresów.', 'en' => 'Secondary self add.']),
            new SettingDefinition('secondary_self_change', 'boolean', true, ['pl' => 'Samodzielna zmiana dodatkowych adresów', 'en' => 'Secondary self change'], ['pl' => 'Samodzielna zmiana dodatkowych adresów.', 'en' => 'Secondary self change.']),
            new SettingDefinition('secondary_self_remove', 'boolean', true, ['pl' => 'Samodzielne usuwanie dodatkowych adresów', 'en' => 'Secondary self remove'], ['pl' => 'Samodzielne usuwanie dodatkowych adresów.', 'en' => 'Secondary self remove.']),
            new SettingDefinition('secondary_email_sharing', 'boolean', false, ['pl' => 'Współdzielenie dodatkowych adresów', 'en' => 'Secondary email sharing'], ['pl' => 'Współdzielenie dodatkowych adresów.', 'en' => 'Secondary email sharing.']),
            new SettingDefinition('secondary_email_limit', 'integer', 5, ['pl' => 'Limit dodatkowych adresów', 'en' => 'Secondary email limit'], ['pl' => 'Limit dodatkowych adresów.', 'en' => 'Secondary email limit.'], rules: ['min:1', 'max:20']),
            new SettingDefinition('avatar_enabled', 'boolean', true, ['pl' => 'Avatar', 'en' => 'Avatar enabled'], ['pl' => 'Avatar.', 'en' => 'Avatar enabled.']),
            new SettingDefinition('avatar_editor', 'string', 'both', ['pl' => 'Uprawnieni do edycji avatara', 'en' => 'Avatar editor'], ['pl' => 'Uprawnieni do edycji avatara.', 'en' => 'Avatar editor.'], rules: ['in:user,admin,both,neither']),
            new SettingDefinition('avatar_max_kib', 'integer', 2048, ['pl' => 'Maksymalny rozmiar avatara w KiB', 'en' => 'Avatar max kib'], ['pl' => 'Maksymalny rozmiar avatara w KiB.', 'en' => 'Avatar max kib.'], rules: ['min:1', 'max:5120']),
            new SettingDefinition('own_login_history', 'boolean', false, ['pl' => 'Własna historia logowania', 'en' => 'Own login history'], ['pl' => 'Własna historia logowania.', 'en' => 'Own login history.']),
            new SettingDefinition('own_account_history', 'boolean', false, ['pl' => 'Własna historia konta', 'en' => 'Own account history'], ['pl' => 'Własna historia konta.', 'en' => 'Own account history.']),
            new SettingDefinition('self_deactivate', 'boolean', false, ['pl' => 'Samodzielna dezaktywacja konta', 'en' => 'Self deactivate'], ['pl' => 'Samodzielna dezaktywacja konta.', 'en' => 'Self deactivate.']),
            new SettingDefinition('self_delete', 'boolean', false, ['pl' => 'Samodzielne usunięcie konta', 'en' => 'Self delete'], ['pl' => 'Samodzielne usunięcie konta.', 'en' => 'Self delete.']),
            new SettingDefinition('email_verification', 'string', 'all', ['pl' => 'Weryfikacja adresu e-mail', 'en' => 'Email verification'], ['pl' => 'Weryfikacja adresu e-mail.', 'en' => 'Email verification.'], rules: ['in:all,new,disabled']),
            new SettingDefinition('password_min_length', 'integer', 12, ['pl' => 'Minimalna długość hasła', 'en' => 'Password min length'], ['pl' => 'Minimalna długość hasła.', 'en' => 'Password min length.'], rules: ['min:12', 'max:72']),
            new SettingDefinition('password_mixed_case', 'boolean', false, ['pl' => 'Wielkie i małe litery w haśle', 'en' => 'Password mixed case'], ['pl' => 'Wielkie i małe litery w haśle.', 'en' => 'Password mixed case.']),
            new SettingDefinition('password_numbers', 'boolean', false, ['pl' => 'Cyfry w haśle', 'en' => 'Password numbers'], ['pl' => 'Cyfry w haśle.', 'en' => 'Password numbers.']),
            new SettingDefinition('password_symbols', 'boolean', false, ['pl' => 'Symbole w haśle', 'en' => 'Password symbols'], ['pl' => 'Symbole w haśle.', 'en' => 'Password symbols.']),
            new SettingDefinition('password_history', 'integer', 5, ['pl' => 'Historia haseł', 'en' => 'Password history'], ['pl' => 'Historia haseł.', 'en' => 'Password history.'], rules: ['min:0', 'max:24']),
            new SettingDefinition('password_expiry_days', 'integer', 0, ['pl' => 'Ważność hasła w dniach', 'en' => 'Password expiry days'], ['pl' => 'Ważność hasła w dniach.', 'en' => 'Password expiry days.'], rules: ['min:0', 'max:3650']),
            new SettingDefinition('password_change_factor', 'boolean', false, ['pl' => 'Drugi składnik przy zmianie hasła', 'en' => 'Password change factor'], ['pl' => 'Drugi składnik przy zmianie hasła.', 'en' => 'Password change factor.']),
            new SettingDefinition('password_session_policy', 'string', 'revoke_others', ['pl' => 'Sesje po zmianie hasła', 'en' => 'Password session policy'], ['pl' => 'Sesje po zmianie hasła.', 'en' => 'Password session policy.'], rules: ['in:keep_all,revoke_others,revoke_all']),
            new SettingDefinition('reset_minutes', 'integer', 30, ['pl' => 'Ważność resetu hasła', 'en' => 'Reset minutes'], ['pl' => 'Ważność resetu hasła.', 'en' => 'Reset minutes.'], rules: ['min:5', 'max:60']),
            new SettingDefinition('totp_enabled', 'boolean', true, ['pl' => 'TOTP', 'en' => 'Totp enabled'], ['pl' => 'TOTP.', 'en' => 'Totp enabled.']),
            new SettingDefinition('recovery_codes_enabled', 'boolean', true, ['pl' => 'Kody odzyskiwania', 'en' => 'Recovery codes enabled'], ['pl' => 'Kody odzyskiwania.', 'en' => 'Recovery codes enabled.']),
            new SettingDefinition('email_otp_enabled', 'boolean', false, ['pl' => 'Kod e-mail', 'en' => 'Email otp enabled'], ['pl' => 'Kod e-mail.', 'en' => 'Email otp enabled.']),
            new SettingDefinition('email_otp_minutes', 'integer', 5, ['pl' => 'Ważność kodu e-mail', 'en' => 'Email otp minutes'], ['pl' => 'Ważność kodu e-mail.', 'en' => 'Email otp minutes.'], rules: ['min:1', 'max:10']),
            new SettingDefinition('two_factor_required', 'string', 'optional', ['pl' => 'Wymagany drugi składnik', 'en' => 'Two factor required'], ['pl' => 'Wymagany drugi składnik.', 'en' => 'Two factor required.'], rules: ['in:optional,any,totp,email']),
            new SettingDefinition('trusted_devices_enabled', 'boolean', false, ['pl' => 'Zaufane urządzenia', 'en' => 'Trusted devices enabled'], ['pl' => 'Zaufane urządzenia.', 'en' => 'Trusted devices enabled.']),
            new SettingDefinition('trusted_device_days', 'integer', 30, ['pl' => 'Ważność zaufania w dniach', 'en' => 'Trusted device days'], ['pl' => 'Ważność zaufania w dniach.', 'en' => 'Trusted device days.'], rules: ['min:1', 'max:90']),
            new SettingDefinition('step_up_seconds', 'integer', 300, ['pl' => 'Ważność ponownego potwierdzenia', 'en' => 'Step up seconds'], ['pl' => 'Ważność ponownego potwierdzenia.', 'en' => 'Step up seconds.'], rules: ['min:1', 'max:900']),
            new SettingDefinition('security_attempt_limit', 'integer', 10, ['pl' => 'Limit prób potwierdzenia', 'en' => 'Security attempt limit'], ['pl' => 'Limit prób potwierdzenia.', 'en' => 'Security attempt limit.'], rules: ['min:1', 'max:30']),
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
        return ($values['two_factor_required'] !== 'totp' || $values['totp_enabled'])
            && ($values['two_factor_required'] !== 'email' || $values['email_otp_enabled'])
            && ($values['two_factor_required'] !== 'any' || $values['totp_enabled'] || $values['email_otp_enabled']);
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
