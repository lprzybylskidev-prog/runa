<?php

namespace Runa\Modules\Auth\Management;

use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\ManagementApiTarget;
use Runa\Contracts\Modules\ManagementBinding;
use Runa\Contracts\Modules\ManagementCommandDefinition;
use Runa\Contracts\Modules\ManagementField;
use Runa\Contracts\Modules\ManagementFieldAccess;
use Runa\Contracts\Modules\ManagementOperation;
use Runa\Contracts\Modules\ManagementPage;
use Runa\Contracts\Modules\ManagementPageProvider;
use Runa\Contracts\Modules\ManagementSection;
use Runa\Modules\Auth\Contracts\AuthAdministration;
use Runa\Modules\Auth\Contracts\AuthBulkSessions;
use Runa\Modules\Auth\Contracts\AuthBulkUsers;
use Runa\Modules\Auth\Contracts\AuthIdentities;
use Runa\Modules\Auth\Contracts\AuthInvitations;
use Runa\Modules\Auth\Contracts\AuthLifecycle;
use Runa\Modules\Auth\Contracts\AuthPolicies;
use Runa\Modules\Auth\Contracts\AuthPreferences;
use Runa\Modules\Auth\Contracts\AuthProfiles;
use Runa\Modules\Auth\Contracts\AuthSecurity;
use Runa\Modules\Auth\Contracts\AuthSessions;
use Runa\Modules\Auth\Contracts\BulkUserChange;
use Runa\Modules\Auth\Definitions\IdentitySettings;
use Runa\Modules\Auth\Support\AdministrationFeatures;
use Runa\Modules\Auth\Support\BulkUserOperations;
use Runa\Modules\Auth\Support\DirectoryFields;

/** Explicit source catalogue, never a request-provided method registry. */
final class AdministrationPages implements ManagementPageProvider
{
    /** @var array<string, string> */
    private const FIELD_NAMES = [
        'TOTP setup pending' => 'Konfiguracja TOTP',
        'TOTP setup expiry' => 'Konfiguracja TOTP do',
        'Last login' => 'Ostatnie logowanie',
        'Last activity' => 'Ostatnia aktywność',
        'Operator recovery' => 'Odzyskiwanie (operator)',
        'Avatar set' => 'Avatar ustawiony',
        'Two-factor authentication' => 'Logowanie dwuskładnikowe',
        '2FA required' => 'Wymagane 2FA',
        'Password expired' => 'Hasło wygasło',
        'Pending invitation' => 'Oczekujące zaproszenie',
        'Invitation expires at' => 'Zaproszenie wygasa',
        'Invitation sent at' => 'Zaproszenie wysłano',

        'Account state' => 'Stan konta',
        'Logged in' => 'Zalogowany',
        'Active sessions' => 'Aktywne sesje',
        'Live session login' => 'Logowanie (aktywne sesje)',
        'Live session activity' => 'Aktywność (sesje)',
        'Created at' => 'Utworzono',
        'Updated at' => 'Zaktualizowano',
        'Deleted at' => 'Usunięto',
        'Email verification date' => 'Weryfikacja e-maila',
        'Password set' => 'Hasło ustawione',
        'Password changed' => 'Zmiana hasła',
        'Password expires at' => 'Hasło wygasa',
        'TOTP configured' => 'TOTP skonfigurowane',
        'Email 2FA' => '2FA e-mail',
        'Recovery codes' => 'Kody odzyskiwania',

        'Accounts' => 'Konta',
        'Action' => 'Akcja',
        'Activate At' => 'Termin aktywacji',
        'Active' => 'Aktywne',
        'Add secondary address' => 'Dodaj dodatkowy adres',
        'All accounts (maximum 100)' => 'Wszystkie konta (maksymalnie 100)',
        'Also revoke trusted devices' => 'Unieważnij również zaufane urządzenia',
        'Apply lifecycle change' => 'Zastosuj zmianę cyklu życia',
        'Approved' => 'Zatwierdzone',
        'Attempt Locked Until' => 'Blokada prób do',
        'Authentication blockers' => 'Przeszkody uwierzytelniania',
        'Authentication policy' => 'Zasady uwierzytelniania',
        'Bulk session revocation' => 'Zbiorcze unieważnianie sesji',
        'Change secondary address' => 'Zmień dodatkowy adres',
        'Changes' => 'Zmiany',
        'Create account' => 'Utwórz konto',
        'Creation' => 'Tworzenie',
        'Credentials and sessions' => 'Dane uwierzytelniania i sesje',
        'Current exception version (0 if absent)' => 'Bieżąca wersja wyjątku (0, jeśli brak)',
        'Current policy revision' => 'Bieżąca rewizja zasad',
        'Current preference revision' => 'Bieżąca rewizja preferencji',
        'Current primary email' => 'Bieżący główny adres e-mail',
        'Deactivate At' => 'Termin dezaktywacji',
        'Deleted' => 'Usunięte',
        'Device UUID' => 'UUID urządzenia',
        'Display name' => 'Nazwa wyświetlana',
        'Eligibility' => 'Warunki dostępu',
        'Email' => 'E-mail',
        'Enable selected flag' => 'Włącz wybraną flagę',
        'Enabled' => 'Włączone',
        'Entry and lifecycle' => 'Dostęp i cykl życia',
        'Exceptions' => 'Wyjątki',
        'Exceptions (latest 50)' => 'Wyjątki (ostatnie 50)',
        'Expires At' => 'Termin wygaśnięcia',
        'First Name' => 'Imię',
        'First name' => 'Imię',
        'History' => 'Historia',
        'Invalidate invitation' => 'Unieważnij zaproszenie',
        'Invitations' => 'Zaproszenia',
        'Lifecycle' => 'Cykl życia',
        'Lifecycle operation' => 'Operacja cyklu życia',
        'Live sessions' => 'Aktywne sesje',
        'Login' => 'Login',
        'Name' => 'Nazwa',
        'New email' => 'Nowy adres e-mail',
        'Operation restrictions' => 'Ograniczenia operacji',
        'Password Change Required' => 'Wymagana zmiana hasła',
        'Permission' => 'Uprawnienie',
        'Policy consequences' => 'Skutki zasad',
        'Policy history (latest 50)' => 'Historia zasad (ostatnie 50)',
        'Policy history and restore' => 'Historia i przywracanie zasad',
        'Policy key' => 'Klucz zasady',
        'Preferences' => 'Preferencje',
        'Presentation and delivery' => 'Wygląd i dostarczanie',
        'Primary Email' => 'Główny adres e-mail',
        'Profile' => 'Profil',
        'Reason' => 'Powód',
        'Remove secondary address' => 'Usuń dodatkowy adres',
        'Replace verified email' => 'Zastąp zweryfikowany adres e-mail',
        'Request email change' => 'Zleć zmianę adresu e-mail',
        'Request password reset' => 'Zleć reset hasła',
        'Require verification' => 'Wymagaj weryfikacji',
        'Reset' => 'Przywróć domyślne',
        'Reset account preferences' => 'Zresetuj preferencje konta',
        'Reset second factors' => 'Zresetuj drugie składniki',
        'Restore consequences' => 'Skutki przywrócenia',
        'Restore source revision' => 'Rewizja źródłowa do przywrócenia',
        'Review and publish policy' => 'Przejrzyj i opublikuj zasady',
        'Review and restore policy' => 'Przejrzyj i przywróć zasady',
        'Review summary' => 'Podsumowanie przeglądu',
        'Revoke all sessions' => 'Unieważnij wszystkie sesje',
        'Revoke selected sessions and trust' => 'Unieważnij wybrane sesje i zaufanie',
        'Revoke sessions' => 'Unieważnij sesje',
        'Revoke session' => 'Unieważnij sesję',
        'Revoke trusted device' => 'Unieważnij zaufane urządzenie',
        'Save' => 'Zapisz',
        'Save profile' => 'Zapisz profil',
        'Secondary' => 'Adresy dodatkowe',
        'Secondary address UUID' => 'UUID dodatkowego adresu',
        'Secondary addresses' => 'Dodatkowe adresy',
        'Security' => 'Zabezpieczenia',
        'Security Locked' => 'Blokada bezpieczeństwa',
        'Selected or all accounts' => 'Wybrane lub wszystkie konta',
        'Send or resend invitation' => 'Wyślij lub ponów zaproszenie',
        'Session UUID' => 'UUID sesji',
        'Sessions' => 'Sesje',
        'Set' => 'Ustawiono',
        'Set or revoke exception' => 'Ustaw lub odwołaj wyjątek',
        'Settings' => 'Ustawienia',
        'State' => 'Stan',
        'Surname' => 'Nazwisko',
        'Trust' => 'Zaufanie',
        'Trusted devices' => 'Zaufane urządzenia',
        'Deadline (empty clears)' => 'Termin (puste usuwa termin)',
        'Expiry (within 30 days)' => 'Wygaśnięcie (do 30 dni)',
        'User directory' => 'Lista użytkowników',
        'Users' => 'Użytkownicy',
        'Value (empty revokes)' => 'Wartość (puste odwołuje)',
        'Verification required' => 'Wymagana weryfikacja',
        'Verified' => 'Zweryfikowano',
        'Verified replacement email' => 'Zweryfikowany adres zastępczy',
        'activate' => 'aktywuj',
        'active' => 'aktywne',
        'add' => 'dodaj',
        'address_id' => 'UUID adresu',
        'committed' => 'zatwierdzono',
        'deactivate' => 'dezaktywuj',
        'delete' => 'usuń',
        'device_id' => 'UUID urządzenia',
        'email' => 'e-mail',
        'expires_at' => 'termin wygaśnięcia',
        'key' => 'klucz',
        'name' => 'nazwa',
        'pending' => 'oczekuje',
        'public_id' => 'publiczny UUID',
        'reason' => 'powód',
        'recovery_required' => 'wymagane odzyskanie',
        'replace' => 'zastąp',
        'require_password_change' => 'wymuś zmianę hasła',
        'restore' => 'przywróć',
        'revision' => 'rewizja',
        'running' => 'w toku',
        'schedule_activation' => 'zaplanuj aktywację',
        'schedule_deactivation' => 'zaplanuj dezaktywację',
        'session_id' => 'UUID sesji',
        'set_attempt_lock' => 'ustaw blokadę prób',
        'set_security_lock' => 'ustaw blokadę bezpieczeństwa',
        'value' => 'wartość',
        'verified' => 'zweryfikowano',
        'version' => 'wersja',
    ];

    public function permissions(): array
    {
        return [];
    }

    public function actions(): array
    {
        return [];
    }

    public function components(): array
    {
        return [];
    }

    public function contributions(): array
    {
        return [];
    }

    public function widgets(): array
    {
        return [];
    }

    /** @return array<string,array<string,array{title:string|array{pl:string,en:string},operation:string,permission:string,inputs:list<string>,proof:?string,destructive:bool,preview:bool}>> */
    public static function commands(): array
    {
        $command = static fn (string|array $title, string $operation, string $permission, array $inputs = [], ?string $proof = null, bool $destructive = false, bool $preview = false): array => compact('title', 'operation', 'permission', 'inputs', 'proof', 'destructive', 'preview');

        $commands = [
            'creation' => ['create' => $command('Create account', 'identity.create', 'users.create', CreationSection::INPUTS, 'runa.auth.users.create')],
            'lifecycle' => ['change' => $command('Apply lifecycle change', 'identity.change', 'users.manage', ['operation', 'at', 'enabled'], destructive: true)],
            'invitations' => ['invite' => $command('Send or resend invitation', 'invitation.issue', 'invitations.manage'),
                'invalidate' => $command('Invalidate invitation', 'invitation.invalidate', 'invitations.manage', destructive: true)],
            'primary_email' => ['change' => $command('Request email change', 'email.admin_change', 'emails.manage', ['email'], 'runa.auth.email.admin_change'),
                'verify' => $command(['pl' => 'Potwierdź główny adres e-mail', 'en' => 'Verify primary email'], 'email.admin_verify', 'emails.verify_bypass', proof: 'runa.auth.email.admin_bypass', destructive: true),
                'replace' => $command('Replace verified email', 'email.admin_bypass', 'emails.verify_bypass', ['replacement_email'], 'runa.auth.email.admin_bypass', true),
                'require_verification' => $command('Require verification', 'email.require_verification', 'emails.reverify', proof: 'runa.auth.email.require_verification', destructive: true)],
            'secondary' => ['add' => $command('Add secondary address', 'secondary.add', 'secondary.manage', ['email']),
                'change' => $command('Change secondary address', 'secondary.change', 'secondary.manage', ['address_id', 'email']),
                'verify' => $command(['pl' => 'Potwierdź dodatkowy adres e-mail', 'en' => 'Verify secondary email'], 'secondary.admin_verify', 'emails.verify_bypass', ['address_id'], 'runa.auth.email.admin_bypass', true),
                'remove' => $command('Remove secondary address', 'secondary.remove', 'secondary.manage', ['address_id'], destructive: true)],
            'sessions' => ['revoke' => $command('Revoke session', 'sessions.revoke', 'sessions.manage', ['session_id'], destructive: true),
                'revoke_all' => $command('Revoke all sessions', 'sessions.revoke_all', 'sessions.manage', destructive: true)],
            'security' => ['password_reset' => $command('Request password reset', 'password.admin_reset', 'passwords.reset', proof: 'runa.auth.password.admin_reset', destructive: true),
                'factor_reset' => $command('Reset second factors', 'two_factor.admin_reset', 'two_factor.reset', proof: 'runa.auth.two_factor.reset', destructive: true)],
            'trust' => ['revoke_all' => $command('Revoke all trusted devices', 'trust.revoke_all', 'trust.manage', destructive: true), 'revoke_device' => $command('Revoke trusted device', 'trust.revoke', 'trust.manage', ['device_id'], destructive: true)],
            'preferences' => ['save' => $command('Save preferences', 'preferences.admin_write', 'preferences.manage', ['locale', 'theme', 'delivery']), 'reset' => $command('Reset account preferences', 'preferences.admin_reset', 'preferences.reset', ['preference_revision'], destructive: true)],
            'exceptions' => ['revoke_all' => $command('Revoke all exceptions', 'policy.revoke_exceptions', 'policy.exceptions', proof: 'runa.auth.policy.exception', destructive: true), 'set' => $command('Set or revoke exception', 'policy.exception', 'policy.exceptions', ['key', 'value', 'reason', 'expires_at', 'exception_version', 'policy_revision'], 'runa.auth.policy.exception', true)],
            'bulk_sessions' => ['revoke' => $command('Revoke sessions', 'sessions.bulk_revoke', 'sessions.bulk_revoke', ['public_ids', ...(app(AdministrationFeatures::class)->enabled('trust') ? ['trust'] : [])], null, true, true)],
        ];

        $labels = [
            'activate' => ['Aktywuj konta', 'Activate accounts'], 'deactivate' => ['Dezaktywuj konta', 'Deactivate accounts'],
            'delete' => ['Usuń konta', 'Delete accounts'], 'restore' => ['Przywróć konta', 'Restore accounts'],
            'schedule_activation' => ['Termin aktywacji', 'Activation schedule'],
            'schedule_deactivation' => ['Termin dezaktywacji', 'Deactivation schedule'],
            'set_attempt_lock' => ['Blokada prób', 'Attempt lock'],
            'set_security_lock' => ['Blokada bezpieczeństwa', 'Security lock'],
            'toggle_activation' => ['Odwróć aktywność', 'Invert activity'],
            'toggle_deletion' => ['Odwróć stan usunięcia', 'Invert deletion state'],
            'toggle_security_lock' => ['Odwróć blokadę bezpieczeństwa', 'Invert security lock'],
            'require_password_change' => ['Wymuś zmianę hasła', 'Force password change'],
            'invite' => ['Wyślij zaproszenia', 'Send invitations'],
            'invalidate_invitation' => ['Unieważnij zaproszenia', 'Invalidate invitations'],
            'require_verification' => ['Ponów weryfikację e-maili', 'Reverify emails'],
            'password_reset' => ['Zleć reset haseł', 'Request password resets'],
            'factor_reset' => ['Zresetuj drugie składniki', 'Reset second factors'],
            'revoke_trust' => ['Unieważnij zaufane urządzenia', 'Revoke trusted devices'],
            'reset_preferences' => ['Zresetuj preferencje kont', 'Reset account preferences'],
            'remove_avatar' => ['Usuń avatary', 'Remove avatars'],
            'revoke_exceptions' => ['Odwołaj wyjątki zasad', 'Revoke policy exceptions'],
        ];
        $commands['bulk_users'] = [];
        foreach (BulkUserChange::OPERATIONS as $operation) {
            if (! BulkUserOperations::available($operation)) {
                continue;
            }
            $access = BulkUserOperations::access($operation);
            $inputs = ['public_ids'];
            if (in_array($operation, ['schedule_activation', 'schedule_deactivation', 'set_attempt_lock'], true)) {
                $inputs[] = 'at';
            }
            if ($operation === 'set_security_lock') {
                $inputs[] = 'enabled';
            }
            $spec = $command($labels[$operation][1], $access['operation'], $access['permission'], $inputs, BulkUserOperations::proof($operation) === 'none' ? null : 'runa.auth.users.bulk.'.$operation, true, true);
            $spec['title'] = ['pl' => $labels[$operation][0], 'en' => $labels[$operation][1]];
            $commands['bulk_users'][$operation] = $spec;
        }

        return $commands;
    }

    /** @return list<ManagementSection> */
    public function userSections(): array
    {
        $sections = [new ManagementSection('directory', 'User directory', 'runa.auth.users.read', null, 'directory', self::directoryFields(), view: 'table')];
        $fields = [
            'eligibility' => [self::field('blockers', 'Authentication blockers', read: 'eligibility.read'), self::field('restrictions', 'Operation restrictions', read: 'eligibility.read')],
            'creation' => self::creationFields(),
            'lifecycle' => [self::field('operation', 'Lifecycle operation', 'dictionary', 'users.manage', 'users.read', choices: ['activate', 'deactivate', 'delete', 'restore', 'schedule_activation', 'schedule_deactivation', 'set_attempt_lock', 'set_security_lock', 'require_password_change']),
                self::field('at', 'Deadline (empty clears)', 'datetime', 'users.manage', 'users.read'), self::field('enabled', 'Enable selected flag', 'boolean', 'users.manage', 'users.read'),
                ...array_map(fn ($key) => self::field($key, ucwords(str_replace('_', ' ', $key)), in_array($key, ['active', 'deleted', 'security_locked', 'password_change_required'], true) ? 'boolean' : 'datetime', read: 'users.read'), ['active', 'deleted', 'security_locked', 'password_change_required', 'activate_at', 'deactivate_at', 'attempt_locked_until'])],
            'invitations' => [],
            'primary_email' => [self::field('current_email', 'Current primary email', read: 'emails.read'), self::field('verified', 'Verified', 'boolean', read: 'emails.read'), self::field('verification_required', 'Verification required', 'boolean', read: 'emails.read'), self::field('email', 'New email', write: 'emails.manage', read: 'profiles.read'), self::field('replacement_email', 'Verified replacement email', write: 'emails.verify_bypass', read: 'profiles.read')],
            'secondary' => [self::field('secondary_email_limit', 'Secondary email limit', 'integer', read: 'secondary.read'), self::field('addresses', 'Secondary addresses', read: 'secondary.read'), self::field('address_id', 'Secondary address UUID', 'uuid', 'secondary.manage', 'secondary.read'), self::field('email', 'New email', write: 'secondary.manage', read: 'secondary.read')],
            'sessions' => [self::field('sessions', 'Live sessions', read: 'sessions.manage'), self::field('session_id', 'Session UUID', 'uuid', 'sessions.manage', 'sessions.manage')],
            'security' => [],
            'trust' => [self::field('devices', 'Trusted devices', read: 'trust.manage'), self::field('device_id', 'Device UUID', 'uuid', 'trust.manage', 'identities.read')],
            'preferences' => [self::field('locale', ['pl' => 'Język', 'en' => 'Language'], 'dictionary', 'preferences.manage', 'preferences.read', choices: ['pl', 'en']),
                self::field('theme', ['pl' => 'Motyw', 'en' => 'Theme'], 'dictionary', 'preferences.manage', 'preferences.read', choices: ['light', 'dark', 'system']),
                self::field('delivery', 'Email preferences', write: 'preferences.manage', read: 'preferences.read'),
                self::field('recipient_selection_enabled', 'Secondary email selection', 'boolean', read: 'preferences.read'), self::field('categories', 'Email categories', read: 'preferences.read'), self::field('recipients', 'Recipients', read: 'preferences.read'),
                self::field('preference_revision', 'Current preference revision', 'integer', 'preferences.reset', 'preferences.read')],
            'exceptions' => [self::field('exception_versions', 'Exception versions', read: 'policy.exceptions'), self::field('exceptions', 'Exceptions (latest 50)', read: 'policy.exceptions'), self::field('key', 'Policy key', 'dictionary', 'policy.exceptions', 'policy.exceptions', choices: ['password_expiry_days', 'session_idle_minutes']), self::field('value', 'Value (empty revokes)', 'integer', 'policy.exceptions', 'policy.exceptions'), self::field('reason', 'Reason', write: 'policy.exceptions', read: 'policy.exceptions'), self::field('expires_at', 'Expiry (within 30 days)', 'datetime', 'policy.exceptions', 'policy.exceptions'), self::field('exception_version', 'Current exception version (0 if absent)', 'integer', 'policy.exceptions', 'policy.exceptions'), self::field('policy_revision', 'Current policy revision', 'integer', 'policy.exceptions', 'policy.exceptions')],
        ];
        $features = app(AdministrationFeatures::class);
        foreach (['email' => ['Email', 'text', 'emails.read'], 'verified' => ['Verified', 'boolean', 'emails.read'], 'email_verified_at' => ['Email verification date', 'datetime', 'emails.read'], 'login' => ['Login', 'text', 'profiles.read'],
            'logged_in' => ['Logged in', 'boolean', 'sessions.manage'], 'session_count' => ['Active sessions', 'integer', 'sessions.manage'],
            'authenticated_at' => ['Live session login', 'datetime', 'sessions.manage'], 'activity_at' => ['Live session activity', 'datetime', 'sessions.manage'],
            'created_at' => ['Created at', 'datetime', 'users.read'], 'updated_at' => ['Updated at', 'datetime', 'users.read'],
            'deleted_at' => ['Deleted at', 'datetime', 'users.read'], 'password_set' => ['Password set', 'boolean', 'passwords.reset'],
            'password_changed_at' => ['Password changed', 'datetime', 'passwords.reset'], 'password_expires_at' => ['Password expires at', 'datetime', 'passwords.reset'],
            'totp_enrolled' => ['TOTP configured', 'boolean', 'two_factor.reset'], 'email_factor_enrolled' => ['Email 2FA', 'boolean', 'two_factor.reset'],
            'recovery_codes_remaining' => ['Recovery codes', 'integer', 'two_factor.reset']] as $key => [$label, $type, $read]) {
            $fields['lifecycle'][] = self::field($key, $label, $type, read: $read);
        }
        $known = array_map(fn ($access) => $access->field->key, $fields['lifecycle']);
        foreach (DirectoryFields::definitions() as $key => [$label, $type, $grant, $feature]) {
            if ($feature !== null && ! $features->enabled($feature)) {
                continue;
            }
            $access = self::field($key, $label, $type, read: $grant);
            if (! in_array($key, [...$known, 'display_name', 'state'], true)) {
                $fields['lifecycle'][] = $access;
            }
            if (str_starts_with($key, 'invitation_')) {
                $fields['invitations'][] = $access;
            }
            if (in_array($key, ['two_factor_enabled', 'two_factor_required', 'totp_setup_pending', 'totp_pending_until', 'totp_enrolled', 'email_factor_enrolled', 'recovery_codes_remaining', 'password_set', 'password_changed_at', 'password_expires_at', 'password_expired', 'password_change_required'], true)) {
                $fields['security'][] = $access;
            }
        }
        $fields['lifecycle'][0] = self::field('operation', 'Lifecycle operation', 'dictionary', 'users.manage', 'users.read',
            choices: array_values(array_filter($fields['lifecycle'][0]->field->choices, $features->lifecycle(...))));
        foreach ($fields as $key => $items) {
            $fields[$key] = array_values(array_filter($items, fn ($access) => ($key === 'creation' && $access->field->key === 'password_change_required')
                || $features->field($access->field->key)));
        }
        if (! $features->enabled('secondary')) {
            unset($fields['secondary']);
        }
        if (! $features->enabled('trust')) {
            unset($fields['trust']);
        }
        $fields['exceptions'][2] = self::field('key', 'Policy key', 'dictionary', 'policy.exceptions', 'policy.exceptions',
            choices: $features->enabled('password_expiry') ? ['password_expiry_days', 'session_idle_minutes'] : ['session_idle_minutes']);
        $fields['exceptions'][] = self::field('active_exceptions', 'Active account rules', read: 'policy.exceptions');
        $reads = ['eligibility' => 'eligibility.read', 'creation' => 'users.create', 'lifecycle' => 'users.read', 'invitations' => 'identities.read', 'primary_email' => 'profiles.read', 'secondary' => 'secondary.read', 'sessions' => 'sessions.manage', 'security' => 'identities.read', 'trust' => 'trust.manage', 'preferences' => 'preferences.read', 'exceptions' => 'policy.exceptions'];
        foreach ($fields as $key => $items) {
            $sections[] = new ManagementSection($key, ucwords(str_replace('_', ' ', $key)), 'runa.auth.'.$reads[$key], 'runa.auth.'.$reads[$key], $key, $items,
                $key === 'eligibility' ? [] : self::commandDefinitions($key), required: false, component: 'runa.auth.admin_section');
        }

        if ($features->enabled('avatar')) {
            $sections[] = new ManagementSection('avatar', ['pl' => 'Avatar', 'en' => 'Avatar'], 'runa.auth.avatars.manage', 'runa.auth.avatars.manage', 'avatar', [
                self::field('has_avatar', ['pl' => 'Avatar ustawiony', 'en' => 'Avatar set'], 'boolean', read: 'avatars.manage'),
                self::field('avatar_initials', ['pl' => 'Inicjały', 'en' => 'Initials'], read: 'avatars.manage'),
                self::field('avatar_upload_pending', ['pl' => 'Wybrano plik', 'en' => 'File selected'], 'boolean', 'avatars.manage', 'avatars.manage'),
                self::field('avatar_max_kib', ['pl' => 'Limit rozmiaru (KiB)', 'en' => 'Size limit (KiB)'], 'integer', read: 'avatars.manage'),
            ], required: false, component: 'runa.auth.admin_section');
        }
        $sections[] = self::bulkSection();
        $sections[] = new ManagementSection('bulk_users', ['pl' => 'Akcje zbiorcze', 'en' => 'Bulk actions'], 'runa.auth.users.read', 'runa.auth.users.read', 'bulk_users', [
            new ManagementFieldAccess(new ManagementField('public_ids', 'uuid', ['pl' => 'Wybrane konta', 'en' => 'Selected accounts'], ['pl' => 'Wybrane konta', 'en' => 'Selected accounts'], writable: true, multiple: true), 'runa.auth.users.read', 'runa.auth.users.read'),
            self::field('at', 'Deadline (empty clears)', 'datetime', 'users.read', 'users.read'),
            self::field('enabled', 'Enable selected flag', 'boolean', 'users.read', 'users.read'),
            self::field('summary', 'Review summary', read: 'users.read'),
        ], self::commandDefinitions('bulk_users'), required: false, component: 'runa.auth.admin_section');

        return $sections;
    }

    /** @return list<ManagementFieldAccess> */
    private static function directoryFields(): array
    {
        $fields = [];
        foreach (DirectoryFields::definitions() as $key => [$label, $type, $grant, $feature]) {
            if ($feature === null || app(AdministrationFeatures::class)->enabled($feature)) {
                $fields[] = self::field($key, $label, $type, read: $grant, filter: true, choices: $key === 'state' ? ['current', 'deleted', 'all'] : []);
            }
        }

        return $fields;
    }

    private static function bulkSection(): ManagementSection
    {
        return new ManagementSection('bulk_sessions', 'Revoke selected sessions and trust', 'runa.auth.sessions.bulk_revoke', 'runa.auth.sessions.bulk_revoke', 'bulk_sessions', [
            new ManagementFieldAccess(new ManagementField('public_ids', 'uuid', ['pl' => 'Wybrane konta', 'en' => 'Selected accounts'], ['pl' => 'Wybrane konta', 'en' => 'Selected accounts'], writable: true, multiple: true), 'runa.auth.sessions.bulk_revoke', 'runa.auth.sessions.bulk_revoke'),
            ...(app(AdministrationFeatures::class)->enabled('trust') ? [self::field('trust', 'Also revoke trusted devices', 'boolean', 'sessions.bulk_revoke', 'sessions.bulk_revoke')] : []),
            self::field('summary', 'Review summary', read: 'sessions.bulk_revoke'),
        ], self::commandDefinitions('bulk_sessions'), required: false, component: 'runa.auth.admin_section');
    }

    public function pages(): array
    {
        $sections = [];
        $settings = array_values(array_filter((new IdentitySettings)->settings(), fn ($setting) => ! $setting->sensitive));
        foreach (array_chunk($settings, 30) as $group => $definitions) {
            $fields = [];
            foreach ($definitions as $setting) {
                $choices = [];
                foreach ($setting->rules as $rule) {
                    if (is_string($rule) && str_starts_with($rule, 'in:')) {
                        $choices = explode(',', substr($rule, 3));
                    }
                }
                $fields[] = self::field($setting->key, $setting->names, $choices !== [] ? 'dictionary' : match ($setting->type) {
                    'boolean' => 'boolean', 'integer' => 'integer', default => 'text'
                }, 'policy.write', 'policy.read', choices: $choices);
            }
            $inputs = array_map(fn ($field) => $field->field->key, $fields);
            $fields[] = self::field('summary', 'Policy consequences', read: 'policy.read');
            $binding = 'policy'.$group;
            $sections[] = new ManagementSection($binding, ['Presentation and delivery', 'Credentials and sessions', 'Entry and lifecycle'][$group], 'runa.auth.policy.read', 'runa.auth.policy.write', $binding, $fields, [
                new ManagementCommandDefinition('publish', 'Review and publish policy', new ManagementOperation(new ApiOperation('policy.publish', 'runa.auth.policy.write'), $inputs, 'both', previewRequired: true), $binding, proofAction: 'runa.auth.policy.publish'),
            ]);
        }
        $sections[] = new ManagementSection('history', 'Policy history and restore', 'runa.auth.policy.read', 'runa.auth.policy.write', 'policy_history', [
            self::field('source_revision', 'Restore source revision', 'integer', 'policy.write', 'policy.read'), self::field('history', 'Policy history (latest 50)', read: 'policy.read'), self::field('summary', 'Restore consequences', read: 'policy.read'),
        ], [new ManagementCommandDefinition('restore', 'Review and restore policy', new ManagementOperation(new ApiOperation('policy.restore', 'runa.auth.policy.write'), ['source_revision'], 'both', previewRequired: true), 'policy_history', destructive: true, proofAction: 'runa.auth.policy.publish')], component: 'runa.auth.admin_section');

        return [new ManagementPage('policy', 'Authentication policy', 'runa.auth.policy.read', $sections, order: 20)];
    }

    private static function creationFields(): array
    {
        $fields = [self::field('email', 'Email', write: 'users.create', read: 'users.create'), ...self::names('users.create')];
        foreach (['active' => ['Aktywne', 'Active'], 'email_verified' => ['Adres potwierdzony przez administratora', 'Email verified by administrator'],
            'security_locked' => ['Blokada bezpieczeństwa', 'Security lock'], 'password_change_required' => ['Zmiana hasła przy pierwszym logowaniu', 'Change password at first sign-in']] as $key => [$pl, $en]) {
            $fields[] = self::field($key, compact('pl', 'en'), 'boolean', 'users.create', 'users.create');
        }
        foreach (['activate_at' => ['Termin aktywacji', 'Activation date'], 'deactivate_at' => ['Termin dezaktywacji', 'Deactivation date'],
            'attempt_locked_until' => ['Blokada logowania do', 'Login locked until']] as $key => [$pl, $en]) {
            $fields[] = self::field($key, compact('pl', 'en'), 'datetime', 'users.create', 'users.create');
        }
        $fields[] = self::field('credential_mode', ['pl' => 'Pierwsze hasło', 'en' => 'Initial password'], 'dictionary', 'users.create', 'users.create', choices: ['none', 'invitation', 'password']);
        foreach (['password' => ['Hasło', 'Password'], 'password_confirmation' => ['Powtórz hasło', 'Confirm password']] as $key => [$pl, $en]) {
            $text = compact('pl', 'en');
            $fields[] = new ManagementFieldAccess(new ManagementField($key, 'text', $text, $text, writable: true, sensitive: true, maxLength: 72), 'runa.auth.users.create', 'runa.auth.users.create');
        }
        $fields[] = self::field('identifier_mode', 'Login identifier', 'dictionary', read: 'users.create', choices: ['email', 'login', 'either']);
        $fields[] = self::field('password_policy', 'Password policy', read: 'users.create');
        $fields[] = self::field('invitation_allowed', 'Invitation available', 'boolean', read: 'invitations.manage');
        $fields[] = self::field('email_verification_allowed', 'Administrative verification available', 'boolean', read: 'emails.verify_bypass');

        return $fields;
    }

    private static function names(string $grant): array
    {
        return array_map(fn ($key) => self::field($key, ucwords(str_replace('_', ' ', $key)), write: $grant, read: $grant), ['name', 'first_name', 'surname', 'login']);
    }

    /** @param string|array{pl:string,en:string} $label */
    public static function field(string $key, string|array $label, string $type = 'text', ?string $write = null, string $read = 'profiles.read', array $choices = [], bool $filter = false): ManagementFieldAccess
    {
        $text = is_array($label) ? $label : ['pl' => self::FIELD_NAMES[$label] ?? $label, 'en' => $label];

        return new ManagementFieldAccess(new ManagementField($key, $type, $text, $text, filterable: $filter, sortable: $filter, writable: $write !== null, required: $write !== null && in_array($key, ['email', 'replacement_email', 'reason', 'expires_at'], true), maxLength: $type === 'text' && $write !== null ? match ($key) {
            'delivery' => 65536, 'name', 'first_name', 'surname' => 160, 'login' => 64, 'email', 'replacement_email' => 254, default => 1000
        } : null, choices: $choices), 'runa.auth.'.$read, $write === null ? null : 'runa.auth.'.$write);
    }

    private static function commandDefinitions(string $key): array
    {
        $result = [];
        foreach (self::commands()[$key] as $name => $spec) {
            if (! app(AdministrationFeatures::class)->command($key, $name)) {
                continue;
            }
            $requirement = $key === 'creation' ? 'password' : ($key === 'bulk_users' ? BulkUserOperations::proof($name) : ($spec['proof'] === null ? 'none' : 'both'));
            $title = $key === 'bulk_sessions' ? ['pl' => 'Unieważnij sesje', 'en' => 'Revoke sessions'] : $spec['title'];
            $result[] = new ManagementCommandDefinition($name, $title, new ManagementOperation(new ApiOperation($spec['operation'], 'runa.auth.'.$spec['permission']), $spec['inputs'], $requirement, revisionRequired: $key !== 'creation', previewRequired: $spec['preview']), $key, destructive: $spec['destructive'], intent: $key === 'creation' ? 'create' : 'action', proofAction: $spec['proof']);
        }

        return $result;
    }

    public function bindings(): array
    {
        $target = static fn ($contract, $methods, $version = '1.0.0') => new ManagementApiTarget('runa.auth', $contract, $version, $methods);
        $identity = $target(AuthIdentities::class, ['identity']);

        return [
            new ManagementBinding('avatar', AvatarSection::class, [$target(AuthAdministration::class, ['facts'], '2.1.0')]),
            new ManagementBinding('directory', Directory::class, [$target(AuthAdministration::class, ['users'], '2.1.0')]),
            new ManagementBinding('eligibility', EligibilitySection::class, [$target(AuthIdentities::class, ['identity', 'eligibility'])]),
            new ManagementBinding('creation', CreationSection::class, [$target(AuthLifecycle::class, ['create'], '2.1.0')]),
            new ManagementBinding('lifecycle', LifecycleSection::class, [$target(AuthAdministration::class, ['lifecycle'], '2.1.0'), $target(AuthLifecycle::class, ['change'], '2.1.0')]),
            new ManagementBinding('invitations', InvitationSection::class, [$target(AuthAdministration::class, ['facts'], '2.1.0'), $identity, $target(AuthInvitations::class, ['invite', 'invalidate'])]),
            new ManagementBinding('primary_email', EmailSection::class, [$target(AuthAdministration::class, ['profile'], '2.1.0'), $target(AuthProfiles::class, ['changeEmail', 'replaceVerifiedEmail', 'verifyEmail', 'requireVerification'], '2.1.0')]),
            new ManagementBinding('secondary', SecondarySection::class, [$target(AuthAdministration::class, ['profile'], '2.1.0'), $target(AuthProfiles::class, ['addSecondary', 'changeSecondary', 'verifySecondary', 'removeSecondary'], '2.1.0')]),
            new ManagementBinding('sessions', SessionSection::class, [$identity, $target(AuthSessions::class, ['listFor', 'revoke', 'revokeAll'])]),
            new ManagementBinding('security', SecuritySection::class, [$target(AuthAdministration::class, ['facts'], '2.1.0'), $identity, $target(AuthSecurity::class, ['resetFactors', 'requestPasswordReset'], '1.2.0')]),
            new ManagementBinding('trust', DeviceSection::class, [$identity, $target(AuthSecurity::class, ['trustedDevices', 'revokeDevice', 'revokeDevices'], '1.2.0')]),
            new ManagementBinding('preferences', PreferenceSection::class, [$target(AuthPreferences::class, ['account', 'updateAccount', 'resetAccount'], '1.1.0')]),
            new ManagementBinding('exceptions', ExceptionSection::class, [$identity, $target(AuthPolicies::class, ['exceptions', 'activeExceptions', 'exceptionVersions', 'exception', 'revokeExceptions'], '1.2.0')]),
            new ManagementBinding('policy0', EntryPolicy::class, [$target(AuthPolicies::class, ['history', 'preview', 'publish', 'restore'], '1.2.0')]),
            new ManagementBinding('policy1', SecurityPolicy::class, [$target(AuthPolicies::class, ['history', 'preview', 'publish', 'restore'], '1.2.0')]),
            new ManagementBinding('policy2', LifecyclePolicy::class, [$target(AuthPolicies::class, ['history', 'preview', 'publish', 'restore'], '1.2.0')]),
            new ManagementBinding('policy_history', PolicyHistorySection::class, [$target(AuthPolicies::class, ['history', 'preview', 'publish', 'restore'], '1.2.0')]),
            new ManagementBinding('bulk_users', BulkUserSection::class, [$target(AuthBulkUsers::class, ['preview', 'execute'], '2.0.0')]),
            new ManagementBinding('bulk_sessions', BulkSessionSection::class, [$target(AuthBulkSessions::class, ['preview', 'revoke'], '2.0.0')]),
        ];
    }
}
