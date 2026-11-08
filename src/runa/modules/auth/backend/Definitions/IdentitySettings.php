<?php

namespace Runa\Modules\Auth\Definitions;

use Illuminate\Database\Connection;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\MutationProof;
use Runa\Contracts\Modules\SettingDefinition;
use Runa\Contracts\Modules\SettingsAccess;
use Runa\Contracts\Modules\SettingsAccessProvider;
use Runa\Contracts\Modules\SettingsContext;
use Runa\Contracts\Modules\SettingsHandler;
use Runa\Contracts\Modules\SettingsParticipant;
use Runa\Contracts\Modules\SettingsProvider;
use Runa\Contracts\Modules\SettingsPublication;
use Runa\Modules\Auth\Actions\PolicyHistory;
use Runa\Modules\Auth\Contracts\DeliveryCategory;
use Runa\Modules\Auth\Contracts\DeliveryProvider;
use Runa\Modules\Auth\Contracts\PreferenceProvider;

/** Immediate lookup/future-deletion policy. Existing ownership is never reassigned. */
final class IdentitySettings implements DeliveryProvider, PreferenceProvider, SettingsAccessProvider, SettingsHandler, SettingsParticipant, SettingsProvider
{
    public function access(): SettingsAccess
    {
        return new SettingsAccess(
            new ApiOperation('policy.read', 'runa.auth.policy.read'),
            new ApiOperation('policy.publish', 'runa.auth.policy.write'),
            proof: new MutationProof('runa.auth.policy.publish', 'password'),
        );
    }

    public function publishing(Connection $connection, SettingsPublication $publication): void
    {
        app(PolicyHistory::class)->publishing($connection, $publication);
    }

    public function settings(): array
    {
        return [
            new SettingDefinition('public_welcome_enabled', 'boolean', true, ['pl' => 'Publiczna strona powitalna', 'en' => 'Public welcome page'], ['pl' => 'Wyłączenie kieruje stronę główną do logowania.', 'en' => 'Disabling redirects the root page to sign-in.']),
            new SettingDefinition('password_policy_effect', 'string', 'future_only', ['pl' => 'Skutki zmiany polityki haseł', 'en' => 'Password policy effects'], ['pl' => 'Określa, czy zmiana wymagań złożoności dotyczy przyszłych haseł, czy wymusza również zmianę istniejących.', 'en' => 'Choose whether changed complexity requirements apply to future passwords only or also require existing passwords to be changed.'], rules: ['in:future_only,force_change']),
            new SettingDefinition('notice_new_browser', 'boolean', false, ['pl' => 'Nowa przeglądarka', 'en' => 'New browser'], ['pl' => 'Wysyła powiadomienie po udanym logowaniu z nierozpoznanej przeglądarki.', 'en' => 'Send a notice after a successful sign-in from an unrecognized browser.']),
            new SettingDefinition('notice_failed_login', 'boolean', false, ['pl' => 'Nieudane logowanie', 'en' => 'Failed login'], ['pl' => 'Powiadamia użytkownika o nieudanej próbie logowania na jego konto.', 'en' => 'Notify the user about a failed sign-in attempt on their account.']),
            new SettingDefinition('notice_locked', 'boolean', true, ['pl' => 'Blokada logowania', 'en' => 'Login lock'], ['pl' => 'Wysyła powiadomienia dotyczące blokady logowania.', 'en' => 'Send notices about sign-in locks.']),
            new SettingDefinition('notice_password_changed', 'boolean', true, ['pl' => 'Zmiana hasła', 'en' => 'Password changed'], ['pl' => 'Wysyła powiadomienie po zmianie hasła do konta.', 'en' => 'Send a notice after the account password changes.']),
            new SettingDefinition('notice_account_deactivation', 'boolean', false, ['pl' => 'Planowana dezaktywacja konta', 'en' => 'Scheduled account deactivation'], ['pl' => 'Wysyła przypomnienia przed zaplanowaną dezaktywacją konta.', 'en' => 'Send reminders before scheduled account deactivation.']),
            new SettingDefinition('deactivation_admin_copy', 'string', null, ['pl' => 'Kopia dla administratora', 'en' => 'Scheduled account deactivation admin copy'], ['pl' => 'UUIDv7 konta odbiorcy lub brak kopii.', 'en' => 'Recipient account UUIDv7 or no copy.'], nullable: true, rules: ['uuid:7']),
            new SettingDefinition('notice_password_expiry', 'boolean', false, ['pl' => 'Wygaśnięcie hasła', 'en' => 'Password expiry'], ['pl' => 'Wysyła przypomnienia przed upływem ważności hasła.', 'en' => 'Send reminders before the password expires.']),
            new SettingDefinition('notice_interval_minutes', 'integer', 60, ['pl' => 'Odstęp wiadomości', 'en' => 'Notice interval'], ['pl' => 'Minimalny odstęp wiadomości kategorii.', 'en' => 'Minimum interval per notice category.'], rules: ['min:1', 'max:10080']),
            new SettingDefinition('expiry_start_days', 'integer', 7, ['pl' => 'Wyprzedzenie przypomnień', 'en' => 'Reminder lead'], ['pl' => 'Liczba dni przed terminem.', 'en' => 'Days before deadline.'], rules: ['min:1', 'max:90']),
            new SettingDefinition('expiry_interval_hours', 'integer', 24, ['pl' => 'Odstęp przypomnień', 'en' => 'Reminder interval'], ['pl' => 'Odstęp w godzinach.', 'en' => 'Interval in hours.'], rules: ['min:1', 'max:720']),
            new SettingDefinition('expiry_max_count', 'integer', 3, ['pl' => 'Limit przypomnień', 'en' => 'Reminder limit'], ['pl' => 'Limit dla jednego terminu.', 'en' => 'Limit for one deadline.'], rules: ['min:1', 'max:20']),
            new SettingDefinition('own_name_editable', 'boolean', true, ['pl' => 'Samodzielna edycja imienia i nazwiska', 'en' => 'Own name editable'], ['pl' => 'Pozwala użytkownikowi zmieniać własne imię i nazwisko w profilu.', 'en' => 'Allow users to change their own first name and surname in their profile.']),
            new SettingDefinition('own_login_editable', 'boolean', true, ['pl' => 'Samodzielna edycja loginu', 'en' => 'Own login editable'], ['pl' => 'Pozwala użytkownikowi zmieniać własny login.', 'en' => 'Allow users to change their own login name.']),
            new SettingDefinition('own_email_editable', 'boolean', true, ['pl' => 'Samodzielna zmiana adresu e-mail', 'en' => 'Own email editable'], ['pl' => 'Pozwala użytkownikowi rozpocząć zmianę głównego adresu e-mail.', 'en' => 'Allow users to start changing their primary email address.']),
            new SettingDefinition('email_change_old_confirmation', 'boolean', false, ['pl' => 'Potwierdzenie przez poprzedni adres', 'en' => 'Email change old confirmation'], ['pl' => 'Zmiana głównego adresu wymaga także potwierdzenia przez dotychczasowy adres e-mail.', 'en' => 'Changing the primary address also requires confirmation through the previous email address.']),
            new SettingDefinition('email_change_minutes', 'integer', 60, ['pl' => 'Ważność zmiany adresu e-mail', 'en' => 'Email change minutes'], ['pl' => 'Czas ważności potwierdzeń zmiany głównego adresu e-mail, w minutach.', 'en' => 'Lifetime of primary email change confirmations, in minutes.'], rules: ['min:5', 'max:1440']),
            new SettingDefinition('email_change_session_policy', 'string', 'revoke_others', ['pl' => 'Sesje po zmianie adresu e-mail', 'en' => 'Email change session policy'], ['pl' => 'Określa, które sesje pozostaną aktywne po zmianie głównego adresu e-mail.', 'en' => 'Choose which sessions remain active after a primary email change.'], rules: ['in:keep_all,revoke_others,revoke_all']),
            new SettingDefinition('admin_email_verification_bypass', 'boolean', true, ['pl' => 'Administracyjne potwierdzanie adresów e-mail', 'en' => 'Administrative email verification'], ['pl' => 'Udostępnia administratorom operacje potwierdzania adresów e-mail; nadal wymagają właściwych uprawnień i potwierdzenia hasłem.', 'en' => 'Make administrative email verification operations available; current permissions and password confirmation remain required.']),
            new SettingDefinition('secondary_emails_enabled', 'boolean', false, ['pl' => 'Dodatkowe adresy e-mail', 'en' => 'Secondary emails enabled'], ['pl' => 'Udostępnia obsługę dodatkowych adresów e-mail dla kont.', 'en' => 'Enable secondary email addresses for accounts.']),
            new SettingDefinition('secondary_self_add', 'boolean', true, ['pl' => 'Samodzielne dodawanie adresów', 'en' => 'Secondary self add'], ['pl' => 'Pozwala użytkownikowi dodawać własne dodatkowe adresy, gdy obsługa dodatkowych adresów jest włączona.', 'en' => 'Allow users to add their own secondary addresses when secondary emails are enabled.']),
            new SettingDefinition('secondary_self_change', 'boolean', true, ['pl' => 'Samodzielna zmiana dodatkowych adresów', 'en' => 'Secondary self change'], ['pl' => 'Pozwala użytkownikowi zmieniać własne dodatkowe adresy e-mail.', 'en' => 'Allow users to change their own secondary email addresses.']),
            new SettingDefinition('secondary_self_remove', 'boolean', true, ['pl' => 'Samodzielne usuwanie dodatkowych adresów', 'en' => 'Secondary self remove'], ['pl' => 'Pozwala użytkownikowi usuwać własne dodatkowe adresy e-mail.', 'en' => 'Allow users to remove their own secondary email addresses.']),
            new SettingDefinition('secondary_email_sharing', 'boolean', false, ['pl' => 'Współdzielenie dodatkowych adresów', 'en' => 'Secondary email sharing'], ['pl' => 'Dopuszcza powiązanie tego samego dodatkowego adresu e-mail z wieloma kontami.', 'en' => 'Allow the same secondary email address to be associated with multiple accounts.']),
            new SettingDefinition('secondary_email_limit', 'integer', 5, ['pl' => 'Limit dodatkowych adresów', 'en' => 'Secondary email limit'], ['pl' => 'Maksymalna liczba dodatkowych adresów e-mail przypisanych do jednego konta.', 'en' => 'Maximum number of secondary email addresses associated with one account.'], rules: ['min:1', 'max:20']),
            new SettingDefinition('avatar_enabled', 'boolean', true, ['pl' => 'Avatar', 'en' => 'Avatar enabled'], ['pl' => 'Udostępnia zdjęcia profilowe użytkowników i ich edycję zgodnie z wybraną polityką.', 'en' => 'Enable user profile pictures and editing under the selected policy.']),
            new SettingDefinition('avatar_editor', 'string', 'both', ['pl' => 'Uprawnieni do edycji avatara', 'en' => 'Avatar editor'], ['pl' => 'Określa, czy zdjęcie profilowe może zmieniać użytkownik, administrator, obie strony czy żadna z nich.', 'en' => 'Choose whether profile pictures may be changed by the user, administrators, both or neither.'], rules: ['in:user,admin,both,neither']),
            new SettingDefinition('avatar_max_kib', 'integer', 2048, ['pl' => 'Maksymalny rozmiar avatara w KiB', 'en' => 'Avatar max kib'], ['pl' => 'Maksymalny rozmiar przesyłanego zdjęcia profilowego, w KiB.', 'en' => 'Maximum uploaded profile picture size, in KiB.'], rules: ['min:1', 'max:5120']),
            new SettingDefinition('own_login_history', 'boolean', false, ['pl' => 'Własna historia logowania', 'en' => 'Own login history'], ['pl' => 'Udostępnia użytkownikowi historię logowań do jego własnego konta.', 'en' => 'Allow users to view the sign-in history of their own account.']),
            new SettingDefinition('own_account_history', 'boolean', false, ['pl' => 'Własna historia konta', 'en' => 'Own account history'], ['pl' => 'Udostępnia użytkownikowi dozwoloną historię zmian jego własnego konta.', 'en' => 'Allow users to view the permitted change history of their own account.']),
            new SettingDefinition('self_deactivate', 'boolean', false, ['pl' => 'Samodzielna dezaktywacja konta', 'en' => 'Self deactivate'], ['pl' => 'Pozwala użytkownikowi samodzielnie dezaktywować własne konto, z zachowaniem zabezpieczeń operacji.', 'en' => 'Allow users to deactivate their own account, subject to operation safeguards.']),
            new SettingDefinition('self_delete', 'boolean', false, ['pl' => 'Samodzielne usunięcie konta', 'en' => 'Self delete'], ['pl' => 'Pozwala użytkownikowi samodzielnie usunąć własne konto, z zachowaniem zabezpieczeń operacji.', 'en' => 'Allow users to delete their own account, subject to operation safeguards.']),
            new SettingDefinition('email_verification', 'string', 'all', ['pl' => 'Weryfikacja adresu e-mail', 'en' => 'Email verification'], ['pl' => 'Określa wymóg potwierdzenia adresu e-mail: dla wszystkich kont, nowych kont lub wyłączony.', 'en' => 'Require email verification for all accounts, new accounts, or disable the requirement.'], rules: ['in:all,new,disabled']),
            new SettingDefinition('password_min_length', 'integer', 12, ['pl' => 'Minimalna długość hasła', 'en' => 'Password min length'], ['pl' => 'Minimalna liczba znaków wymagana w nowym haśle.', 'en' => 'Minimum number of characters required in a new password.'], rules: ['min:12', 'max:72']),
            new SettingDefinition('password_mixed_case', 'boolean', false, ['pl' => 'Wielkie i małe litery w haśle', 'en' => 'Password mixed case'], ['pl' => 'Nowe hasło musi zawierać zarówno małe, jak i wielkie litery.', 'en' => 'Require both lowercase and uppercase letters in new passwords.']),
            new SettingDefinition('password_numbers', 'boolean', false, ['pl' => 'Cyfry w haśle', 'en' => 'Password numbers'], ['pl' => 'Nowe hasło musi zawierać co najmniej jedną cyfrę.', 'en' => 'Require at least one digit in new passwords.']),
            new SettingDefinition('password_symbols', 'boolean', false, ['pl' => 'Symbole w haśle', 'en' => 'Password symbols'], ['pl' => 'Nowe hasło musi zawierać co najmniej jeden symbol.', 'en' => 'Require at least one symbol in new passwords.']),
            new SettingDefinition('password_history', 'integer', 5, ['pl' => 'Historia haseł', 'en' => 'Password history'], ['pl' => 'Liczba poprzednich haseł blokowanych przy wyborze nowego hasła. Zero wyłącza historię.', 'en' => 'Number of previous passwords blocked when choosing a new password. Zero disables password history.'], rules: ['min:0', 'max:24']),
            new SettingDefinition('password_expiry_days', 'integer', 30, ['pl' => 'Ważność hasła w dniach', 'en' => 'Password expiry days'], ['pl' => 'Liczba dni ważności hasła. Zero wyłącza okresowe wygasanie haseł.', 'en' => 'Password lifetime in days. Zero disables periodic password expiry.'], rules: ['min:0', 'max:3650']),
            new SettingDefinition('password_session_policy', 'string', 'revoke_others', ['pl' => 'Sesje po zmianie hasła', 'en' => 'Password session policy'], ['pl' => 'Określa, które sesje pozostaną aktywne po zmianie hasła.', 'en' => 'Choose which sessions remain active after a password change.'], rules: ['in:keep_all,revoke_others,revoke_all']),
            new SettingDefinition('reset_minutes', 'integer', 30, ['pl' => 'Ważność resetu hasła', 'en' => 'Reset minutes'], ['pl' => 'Czas ważności linku resetowania hasła, w minutach.', 'en' => 'Password reset link lifetime, in minutes.'], rules: ['min:5', 'max:60']),
            new SettingDefinition('totp_enabled', 'boolean', true, ['pl' => 'TOTP', 'en' => 'Totp enabled'], ['pl' => 'Udostępnia kody z aplikacji uwierzytelniającej jako drugi składnik logowania.', 'en' => 'Enable authenticator app codes as a second factor for sign-in.']),
            new SettingDefinition('recovery_codes_enabled', 'boolean', true, ['pl' => 'Kody odzyskiwania', 'en' => 'Recovery codes enabled'], ['pl' => 'Udostępnia jednorazowe kody odzyskiwania dla logowania dwuskładnikowego.', 'en' => 'Enable one-time recovery codes for two-factor sign-in.']),
            new SettingDefinition('email_otp_enabled', 'boolean', false, ['pl' => 'Kod e-mail', 'en' => 'Email otp enabled'], ['pl' => 'Udostępnia jednorazowy kod e-mail jako drugi składnik logowania.', 'en' => 'Enable one-time email codes as a second factor for sign-in.']),
            new SettingDefinition('email_otp_minutes', 'integer', 5, ['pl' => 'Ważność kodu e-mail', 'en' => 'Email otp minutes'], ['pl' => 'Czas ważności jednorazowego kodu logowania wysłanego e-mailem, w minutach.', 'en' => 'Lifetime of a one-time sign-in code sent by email, in minutes.'], rules: ['min:1', 'max:10']),
            new SettingDefinition('two_factor_required', 'string', 'optional', ['pl' => 'Wymagany drugi składnik', 'en' => 'Two factor required'], ['pl' => 'Określa, czy drugi składnik logowania jest opcjonalny, dowolny wymagany, czy wymagany jest konkretny rodzaj.', 'en' => 'Choose whether a second sign-in factor is optional, any available factor is required, or a specific factor is required.'], rules: ['in:optional,any,totp,email']),
            new SettingDefinition('trusted_devices_enabled', 'boolean', false, ['pl' => 'Zaufane urządzenia', 'en' => 'Trusted devices enabled'], ['pl' => 'Pozwala zapamiętać zaufaną przeglądarkę na potrzeby kolejnych logowań.', 'en' => 'Allow a trusted browser to be remembered for subsequent sign-ins.']),
            new SettingDefinition('trusted_device_days', 'integer', 30, ['pl' => 'Ważność zaufania w dniach', 'en' => 'Trusted device days'], ['pl' => 'Okres ważności zaufania do zapamiętanej przeglądarki, w dniach.', 'en' => 'Lifetime of trust in a remembered browser, in days.'], rules: ['min:1', 'max:90']),
            new SettingDefinition('step_up_seconds', 'integer', 300, ['pl' => 'Ważność ponownego potwierdzenia', 'en' => 'Step up seconds'], ['pl' => 'Maksymalny czas ważności potwierdzenia hasłem, w sekundach. Operacje wymagające jednorazowego potwierdzenia nadal zużywają dowód.', 'en' => 'Maximum password confirmation lifetime, in seconds. Operations requiring one-time confirmation still consume the proof.'], rules: ['min:1', 'max:900']),
            new SettingDefinition('security_attempt_limit', 'integer', 10, ['pl' => 'Limit prób potwierdzenia', 'en' => 'Security attempt limit'], ['pl' => 'Limit prób potwierdzania operacji bezpieczeństwa.', 'en' => 'Attempt limit for confirming security operations.'], rules: ['min:1', 'max:30']),
            new SettingDefinition('session_limit', 'integer', 0, ['pl' => 'Limit równoczesnych sesji', 'en' => 'Concurrent session limit'], ['pl' => 'Maksymalna liczba równoczesnych sesji konta. Zero wyłącza limit.', 'en' => 'Maximum number of concurrent account sessions. Zero disables the limit.'], rules: ['min:0', 'max:100']),
            new SettingDefinition('session_idle_minutes', 'integer', 120, ['pl' => 'Limit bezczynności sesji', 'en' => 'Session inactivity timeout'], ['pl' => 'Dopuszczalny czas bezczynności sesji, w minutach.', 'en' => 'Allowed session inactivity period, in minutes.'], rules: ['min:1', 'max:43200']),
            new SettingDefinition('session_absolute_minutes', 'integer', 720, ['pl' => 'Bezwzględna ważność sesji', 'en' => 'Absolute session lifetime'], ['pl' => 'Maksymalny czas trwania sesji od logowania, w minutach; aktywność nie przesuwa tego terminu.', 'en' => 'Maximum session lifetime from sign-in, in minutes; activity does not extend this deadline.'], rules: ['min:1', 'max:43200']),
            new SettingDefinition('session_remember_minutes', 'integer', 0, ['pl' => 'Ważność zapamiętanej sesji', 'en' => 'Remembered session lifetime'], ['pl' => 'Czas ważności zapamiętanej sesji, w minutach, ograniczony także bezwzględnym limitem sesji. Zero wyłącza zapamiętywanie.', 'en' => 'Remembered session lifetime in minutes, also bounded by the absolute session limit. Zero disables remembering sessions.'], rules: ['min:0', 'max:43200']),
            new SettingDefinition('session_heartbeat_seconds', 'integer', 30, ['pl' => 'Odstęp sygnałów aktywności', 'en' => 'Activity heartbeat interval'], ['pl' => 'Minimalny odstęp przesyłania informacji o aktywności użytkownika, w sekundach.', 'en' => 'Minimum interval between user activity reports, in seconds.'], rules: ['min:10', 'max:300']),
            new SettingDefinition('session_warning_seconds', 'integer', 120, ['pl' => 'Wyprzedzenie ostrzeżenia', 'en' => 'Expiry warning interval'], ['pl' => 'Na ile sekund przed końcem sesji wyświetlić ostrzeżenie.', 'en' => 'How many seconds before session expiry to show a warning.'], rules: ['min:0', 'max:3600']),
            new SettingDefinition('abuse_window_seconds', 'integer', 300, ['pl' => 'Okno zliczania prób', 'en' => 'Login counting window'], ['pl' => 'Długość okna zliczania prób logowania, w sekundach.', 'en' => 'Length of the sign-in attempt counting window, in seconds.'], rules: ['min:30', 'max:3600']),
            new SettingDefinition('abuse_account_limit', 'integer', 10, ['pl' => 'Limit nieudanych prób konta', 'en' => 'Account failure limit'], ['pl' => 'Limit nieudanych prób logowania dla konta w bieżącym oknie zliczania.', 'en' => 'Failed sign-in attempt limit for an account in the current counting window.'], rules: ['min:1', 'max:1000']),
            new SettingDefinition('abuse_source_limit', 'integer', 60, ['pl' => 'Limit prób źródła', 'en' => 'Source attempt limit'], ['pl' => 'Limit prób logowania z jednego źródła w bieżącym oknie zliczania.', 'en' => 'Sign-in attempt limit for one source in the current counting window.'], rules: ['min:1', 'max:10000']),
            new SettingDefinition('abuse_lock_seconds', 'integer', 300, ['pl' => 'Czas czasowej blokady logowania', 'en' => 'Temporary login lock duration'], ['pl' => 'Czas trwania czasowej blokady prób logowania, w sekundach.', 'en' => 'Duration of a temporary sign-in attempt lock, in seconds.'], rules: ['min:30', 'max:3600']),
            new SettingDefinition('abuse_escalation_limit', 'integer', 0, ['pl' => 'Próg eskalacji nieudanych prób', 'en' => 'Failure escalation threshold'], ['pl' => 'Próg prób rejestrujący eskalację w audycie do sprawdzenia przez administratora. Zero wyłącza eskalację; nie jest to trwała blokada konta.', 'en' => 'Attempt threshold that records an Audit escalation for administrative review. Zero disables escalation; this is not a permanent account lock.'], rules: ['min:0', 'max:1000']),
            new SettingDefinition('session_extension', 'boolean', true, ['pl' => 'Przedłużanie aktywnością', 'en' => 'Activity extension'], ['pl' => 'Rzeczywista aktywność użytkownika odświeża limit bezczynności, bez wydłużania bezwzględnego terminu sesji.', 'en' => 'Real user activity renews the inactivity limit without extending the absolute session deadline.']),
            new SettingDefinition('public_registration', 'boolean', false, ['pl' => 'Rejestracja publiczna', 'en' => 'Public registration'], ['pl' => 'Udostępnia samodzielne tworzenie kont przez formularz rejestracji.', 'en' => 'Enable self-service account creation through the registration form.']),
            new SettingDefinition('registration_activation', 'string', 'automatic', ['pl' => 'Aktywacja po rejestracji', 'en' => 'Registration activation'], ['pl' => 'Automatyczna aktywacja lub nieaktywne konto do aktywacji przez administratora.', 'en' => 'Automatic activation or an inactive account for administrator activation.'], rules: ['in:automatic,administrator']),
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

    public function deliveryCategories(): array
    {
        $result = [];
        foreach (['new_browser' => ['Nowa przeglądarka', 'New browser'], 'failed_login' => ['Nieudane logowanie', 'Failed login'],
            'locked' => ['Blokada logowania', 'Login lock'], 'password_changed' => ['Zmiana hasła', 'Password changed'],
            'account_deactivation' => ['Planowana dezaktywacja konta', 'Scheduled account deactivation'], 'password_expiry' => ['Wygaśnięcie hasła', 'Password expiry']] as $key => $names) {
            $result[] = new DeliveryCategory($key, ['pl' => $names[0], 'en' => $names[1]],
                mandatory: in_array($key, ['password_changed', 'locked'], true), sensitive: in_array($key, ['password_changed', 'locked'], true));
        }

        return $result;
    }

    public function preferences(): array
    {
        return AccountDefaults::preferences();
    }

    public function validPreference(string $view, string $key, mixed $value): bool
    {
        return $key !== 'expanded' || (is_array($value) && count($value) <= 200
            && count(array_filter($value, fn ($key) => is_string($key) && strlen($key) < 200)) === count($value));
    }

    public function migratePreference(string $view, string $key, mixed $value, int $version): mixed
    {
        return null;
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
