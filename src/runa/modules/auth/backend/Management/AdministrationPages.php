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
use Runa\Modules\Auth\Contracts\AuthIdentities;
use Runa\Modules\Auth\Contracts\AuthInvitations;
use Runa\Modules\Auth\Contracts\AuthLifecycle;
use Runa\Modules\Auth\Contracts\AuthPolicies;
use Runa\Modules\Auth\Contracts\AuthPreferences;
use Runa\Modules\Auth\Contracts\AuthProfiles;
use Runa\Modules\Auth\Contracts\AuthSecurity;
use Runa\Modules\Auth\Contracts\AuthSessions;
use Runa\Modules\Auth\Definitions\IdentitySettings;

/** Explicit source catalogue, never a request-provided method registry. */
final class AdministrationPages implements ManagementPageProvider
{
    /** @var array<string, string> */
    private const FIELD_NAMES = [
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
        'UTC deadline (empty clears)' => 'Termin UTC (puste usuwa termin)',
        'UTC expiry (within 30 days)' => 'Wygaśnięcie UTC (do 30 dni)',
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
        'approve' => 'zatwierdź',
        'approved' => 'zatwierdzone',
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
        'require_password_change' => 'wymagaj zmiany hasła',
        'restore' => 'przywróć',
        'revision' => 'rewizja',
        'running' => 'w toku',
        'schedule_activation' => 'zaplanuj aktywację',
        'schedule_deactivation' => 'zaplanuj dezaktywację',
        'session_id' => 'UUID sesji',
        'set_attempt_lock' => 'ustaw blokadę prób',
        'set_expiry' => 'ustaw wygaśnięcie',
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

    /** @return array<string,array<string,array{title:string,operation:string,permission:string,inputs:list<string>,proof:?string,destructive:bool,preview:bool}>> */
    public static function commands(): array
    {
        $command = static fn (string $title, string $operation, string $permission, array $inputs = [], ?string $proof = null, bool $destructive = false, bool $preview = false): array => compact('title', 'operation', 'permission', 'inputs', 'proof', 'destructive', 'preview');

        return [
            'creation' => ['create' => $command('Create account', 'identity.create', 'users.create', ['email', 'name', 'first_name', 'surname', 'login', 'active', 'approved'])],
            'lifecycle' => ['change' => $command('Apply lifecycle change', 'identity.change', 'users.manage', ['operation', 'at', 'enabled'], destructive: true)],
            'invitations' => ['invite' => $command('Send or resend invitation', 'invitation.issue', 'invitations.manage'),
                'invalidate' => $command('Invalidate invitation', 'invitation.invalidate', 'invitations.manage', destructive: true)],
            'primary_email' => ['change' => $command('Request email change', 'email.admin_change', 'emails.manage', ['email'], 'runa.auth.email.admin_change'),
                'replace' => $command('Replace verified email', 'email.admin_bypass', 'emails.verify_bypass', ['replacement_email'], 'runa.auth.email.admin_bypass', true),
                'require_verification' => $command('Require verification', 'email.require_verification', 'emails.reverify', proof: 'runa.auth.email.require_verification', destructive: true)],
            'secondary' => ['add' => $command('Add secondary address', 'secondary.add', 'secondary.manage', ['email']),
                'change' => $command('Change secondary address', 'secondary.change', 'secondary.manage', ['address_id', 'email']),
                'remove' => $command('Remove secondary address', 'secondary.remove', 'secondary.manage', ['address_id'], destructive: true)],
            'sessions' => ['revoke' => $command('Revoke session', 'sessions.revoke', 'sessions.manage', ['session_id'], destructive: true),
                'revoke_all' => $command('Revoke all sessions', 'sessions.revoke_all', 'sessions.manage', destructive: true)],
            'security' => ['password_reset' => $command('Request password reset', 'password.admin_reset', 'passwords.reset', proof: 'runa.auth.password.admin_reset', destructive: true),
                'factor_reset' => $command('Reset second factors', 'two_factor.admin_reset', 'two_factor.reset', proof: 'runa.auth.two_factor.reset', destructive: true)],
            'trust' => ['revoke_device' => $command('Revoke trusted device', 'trust.revoke', 'trust.manage', ['device_id'], destructive: true)],
            'preferences' => ['reset' => $command('Reset account preferences', 'preferences.admin_reset', 'preferences.reset', ['preference_revision'], destructive: true)],
            'exceptions' => ['set' => $command('Set or revoke exception', 'policy.exception', 'policy.exceptions', ['key', 'value', 'reason', 'expires_at', 'exception_version', 'policy_revision'], 'runa.auth.policy.exception', true)],
            'bulk_sessions' => ['revoke' => $command('Revoke selected sessions and trust', 'sessions.bulk_revoke', 'sessions.bulk_revoke', ['public_ids', 'all', 'trust'], 'runa.auth.sessions.bulk_revoke', true, true)],
        ];
    }

    /** @return list<ManagementSection> */
    public function userSections(): array
    {
        $sections = [new ManagementSection('directory', 'User directory', 'runa.auth.users.read', null, 'directory', [
            self::field('display_name', 'Display name', read: 'users.read', filter: true),
            self::field('state', 'State', read: 'users.read', filter: true),
        ], view: 'table')];
        $fields = [
            'eligibility' => [self::field('blockers', 'Authentication blockers', read: 'eligibility.read'), self::field('restrictions', 'Operation restrictions', read: 'eligibility.read')],
            'creation' => [self::field('email', 'Email', write: 'users.create', read: 'users.create'), ...self::names('users.create'), self::field('active', 'Active', 'boolean', 'users.create', 'users.create'), self::field('approved', 'Approved', 'boolean', 'users.create', 'users.create')],
            'lifecycle' => [self::field('operation', 'Lifecycle operation', 'dictionary', 'users.manage', 'users.read', choices: ['activate', 'deactivate', 'approve', 'delete', 'restore', 'schedule_activation', 'schedule_deactivation', 'set_expiry', 'set_attempt_lock', 'set_security_lock', 'require_password_change']),
                self::field('at', 'UTC deadline (empty clears)', 'datetime', 'users.manage', 'users.read'), self::field('enabled', 'Enable selected flag', 'boolean', 'users.manage', 'users.read'),
                ...array_map(fn ($key) => self::field($key, ucwords(str_replace('_', ' ', $key)), in_array($key, ['active', 'approved', 'deleted', 'security_locked', 'password_change_required'], true) ? 'boolean' : 'datetime', read: 'users.read'), ['active', 'approved', 'deleted', 'security_locked', 'password_change_required', 'activate_at', 'deactivate_at', 'expires_at', 'attempt_locked_until'])],
            'invitations' => [],
            'primary_email' => [self::field('current_email', 'Current primary email', read: 'emails.read'), self::field('verified', 'Verified', 'boolean', read: 'emails.read'), self::field('verification_required', 'Verification required', 'boolean', read: 'emails.read'), self::field('email', 'New email', write: 'emails.manage', read: 'profiles.read'), self::field('replacement_email', 'Verified replacement email', write: 'emails.verify_bypass', read: 'profiles.read')],
            'secondary' => [self::field('addresses', 'Secondary addresses', read: 'secondary.read'), self::field('address_id', 'Secondary address UUID', 'uuid', 'secondary.manage', 'secondary.read'), self::field('email', 'New email', write: 'secondary.manage', read: 'secondary.read')],
            'sessions' => [self::field('sessions', 'Live sessions', read: 'sessions.manage'), self::field('session_id', 'Session UUID', 'uuid', 'sessions.manage', 'sessions.manage')],
            'security' => [],
            'trust' => [self::field('devices', 'Trusted devices', read: 'trust.manage'), self::field('device_id', 'Device UUID', 'uuid', 'trust.manage', 'identities.read')],
            'preferences' => [self::field('preference_revision', 'Current preference revision', 'integer', 'preferences.reset', 'preferences.reset')],
            'exceptions' => [self::field('exceptions', 'Exceptions (latest 50)', read: 'policy.exceptions'), self::field('key', 'Policy key', 'dictionary', 'policy.exceptions', 'policy.exceptions', choices: ['password_expiry_days', 'session_idle_minutes']), self::field('value', 'Value (empty revokes)', 'integer', 'policy.exceptions', 'policy.exceptions'), self::field('reason', 'Reason', write: 'policy.exceptions', read: 'policy.exceptions'), self::field('expires_at', 'UTC expiry (within 30 days)', 'datetime', 'policy.exceptions', 'policy.exceptions'), self::field('exception_version', 'Current exception version (0 if absent)', 'integer', 'policy.exceptions', 'policy.exceptions'), self::field('policy_revision', 'Current policy revision', 'integer', 'policy.exceptions', 'policy.exceptions')],
        ];
        $reads = ['eligibility' => 'eligibility.read', 'creation' => 'users.create', 'lifecycle' => 'users.read', 'invitations' => 'identities.read', 'primary_email' => 'profiles.read', 'secondary' => 'secondary.read', 'sessions' => 'sessions.manage', 'security' => 'identities.read', 'trust' => 'trust.manage', 'preferences' => 'profiles.read', 'exceptions' => 'policy.exceptions'];
        foreach ($fields as $key => $items) {
            $sections[] = new ManagementSection($key, ucwords(str_replace('_', ' ', $key)), 'runa.auth.'.$reads[$key], 'runa.auth.'.$reads[$key], $key, $items,
                $key === 'eligibility' ? [] : self::commandDefinitions($key), required: false, component: 'runa.auth.admin_section');
        }

        return $sections;
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

        return [new ManagementPage('policy', 'Authentication policy', 'runa.auth.policy.read', $sections, order: 20),
            new ManagementPage('bulk_sessions', 'Bulk session revocation', 'runa.auth.sessions.bulk_revoke', [new ManagementSection('bulk_sessions', 'Selected or all accounts', 'runa.auth.sessions.bulk_revoke', 'runa.auth.sessions.bulk_revoke', 'bulk_sessions', [
                new ManagementFieldAccess(new ManagementField('public_ids', 'uuid', ['pl' => 'Konta', 'en' => 'Accounts'], ['pl' => 'Wybrane UUID; pozostaw puste w trybie wszystkich kont.', 'en' => 'Selected UUIDs; leave empty in all mode.'], writable: true, multiple: true), 'runa.auth.sessions.bulk_revoke', 'runa.auth.sessions.bulk_revoke'),
                self::field('all', 'All accounts (maximum 100)', 'boolean', 'sessions.bulk_revoke', 'sessions.bulk_revoke'), self::field('trust', 'Also revoke trusted devices', 'boolean', 'sessions.bulk_revoke', 'sessions.bulk_revoke'), self::field('summary', 'Review summary', read: 'sessions.bulk_revoke'),
            ], self::commandDefinitions('bulk_sessions'))], order: 30)];
    }

    private static function names(string $grant): array
    {
        return array_map(fn ($key) => self::field($key, ucwords(str_replace('_', ' ', $key)), write: $grant, read: $grant), ['name', 'first_name', 'surname', 'login']);
    }

    /** @param string|array{pl:string,en:string} $label */
    public static function field(string $key, string|array $label, string $type = 'text', ?string $write = null, string $read = 'profiles.read', array $choices = [], bool $filter = false): ManagementFieldAccess
    {
        $text = is_array($label) ? $label : ['pl' => self::FIELD_NAMES[$label] ?? $label, 'en' => $label];

        return new ManagementFieldAccess(new ManagementField($key, $type, $text, $text, filterable: $filter, writable: $write !== null, maxLength: $type === 'text' && $write !== null ? match ($key) {
            'name', 'first_name', 'surname' => 160, 'login' => 64, 'email', 'replacement_email' => 254, default => 1000
        } : null, choices: $choices), 'runa.auth.'.$read, $write === null ? null : 'runa.auth.'.$write);
    }

    private static function commandDefinitions(string $key): array
    {
        $result = [];
        foreach (self::commands()[$key] as $name => $spec) {
            $result[] = new ManagementCommandDefinition($name, $spec['title'], new ManagementOperation(new ApiOperation($spec['operation'], 'runa.auth.'.$spec['permission']), $spec['inputs'], $spec['proof'] === null ? 'none' : 'both', revisionRequired: $key !== 'creation', previewRequired: $spec['preview']), $key, destructive: $spec['destructive'], intent: $key === 'creation' ? 'create' : 'action', proofAction: $spec['proof']);
        }

        return $result;
    }

    public function bindings(): array
    {
        $target = static fn ($contract, $methods, $version = '1.0.0') => new ManagementApiTarget('runa.auth', $contract, $version, $methods);
        $identity = $target(AuthIdentities::class, ['identity']);

        return [
            new ManagementBinding('directory', Directory::class, [$target(AuthAdministration::class, ['users'])]),
            new ManagementBinding('eligibility', EligibilitySection::class, [$target(AuthIdentities::class, ['identity', 'eligibility'])]),
            new ManagementBinding('creation', CreationSection::class, [$target(AuthLifecycle::class, ['create'])]),
            new ManagementBinding('lifecycle', LifecycleSection::class, [$target(AuthAdministration::class, ['lifecycle']), $target(AuthLifecycle::class, ['change'])]),
            new ManagementBinding('invitations', InvitationSection::class, [$identity, $target(AuthInvitations::class, ['invite', 'invalidate'])]),
            new ManagementBinding('primary_email', EmailSection::class, [$target(AuthAdministration::class, ['profile']), $target(AuthProfiles::class, ['changeEmail', 'replaceVerifiedEmail', 'requireVerification'], '2.0.0')]),
            new ManagementBinding('secondary', SecondarySection::class, [$target(AuthAdministration::class, ['profile']), $target(AuthProfiles::class, ['addSecondary', 'changeSecondary', 'removeSecondary'], '2.0.0')]),
            new ManagementBinding('sessions', SessionSection::class, [$identity, $target(AuthSessions::class, ['listFor', 'revoke', 'revokeAll'])]),
            new ManagementBinding('security', SecuritySection::class, [$identity, $target(AuthSecurity::class, ['resetFactors', 'requestPasswordReset'])]),
            new ManagementBinding('trust', DeviceSection::class, [$identity, $target(AuthSecurity::class, ['trustedDevices', 'revokeDevice'])]),
            new ManagementBinding('preferences', PreferenceSection::class, [$target(AuthAdministration::class, ['profile']), $target(AuthPreferences::class, ['resetAccount'])]),
            new ManagementBinding('exceptions', ExceptionSection::class, [$identity, $target(AuthPolicies::class, ['exceptions', 'exception'])]),
            new ManagementBinding('policy0', EntryPolicy::class, [$target(AuthPolicies::class, ['history', 'preview', 'publish', 'restore'])]),
            new ManagementBinding('policy1', SecurityPolicy::class, [$target(AuthPolicies::class, ['history', 'preview', 'publish', 'restore'])]),
            new ManagementBinding('policy2', LifecyclePolicy::class, [$target(AuthPolicies::class, ['history', 'preview', 'publish', 'restore'])]),
            new ManagementBinding('policy_history', PolicyHistorySection::class, [$target(AuthPolicies::class, ['history', 'preview', 'publish', 'restore'])]),
            new ManagementBinding('bulk_sessions', BulkSessionSection::class, [$target(AuthBulkSessions::class, ['preview', 'revoke'])]),
        ];
    }
}
