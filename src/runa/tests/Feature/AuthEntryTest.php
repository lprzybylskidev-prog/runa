<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\FortifyServiceProvider;
use PragmaRX\Google2FA\Google2FA;
use Runa\Contracts\Modules\AuthorityGuard;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\Modules\Audit\Contracts\AuditUnavailable;
use Runa\Modules\Auth\Actions\AuthenticateSession;
use Runa\Modules\Auth\Actions\Avatars;
use Runa\Modules\Auth\Actions\BulkSessions;
use Runa\Modules\Auth\Actions\ChangeIdentity;
use Runa\Modules\Auth\Actions\CleanupSessions;
use Runa\Modules\Auth\Actions\CredentialFactors;
use Runa\Modules\Auth\Actions\DeliverEntryMail;
use Runa\Modules\Auth\Actions\EmailAddresses;
use Runa\Modules\Auth\Actions\EntryTokens;
use Runa\Modules\Auth\Actions\ManageProfiles;
use Runa\Modules\Auth\Actions\ManageSecurity;
use Runa\Modules\Auth\Actions\ManageSessions;
use Runa\Modules\Auth\Actions\OperatorAccounts;
use Runa\Modules\Auth\Actions\Passwords;
use Runa\Modules\Auth\Actions\Policies;
use Runa\Modules\Auth\Actions\PolicyHistory;
use Runa\Modules\Auth\Actions\ProfileMail;
use Runa\Modules\Auth\Actions\Profiles;
use Runa\Modules\Auth\Actions\ScheduledLifecycle;
use Runa\Modules\Auth\Actions\SecurityNotices;
use Runa\Modules\Auth\Actions\SessionStore;
use Runa\Modules\Auth\Actions\StepUp;
use Runa\Modules\Auth\Contracts\AuthInvitations;
use Runa\Modules\Auth\Contracts\AuthPolicies;
use Runa\Modules\Auth\Contracts\AuthPreferences;
use Runa\Modules\Auth\Contracts\AuthProfiles;
use Runa\Modules\Auth\Contracts\AuthSecurity;
use Runa\Modules\Auth\Contracts\AuthStepUp;
use Runa\Modules\Auth\Contracts\BulkUserSelection;
use Runa\Modules\Auth\Contracts\IdentityConflict;
use Runa\Modules\Auth\Contracts\LifecycleChange;
use Runa\Modules\Auth\Contracts\NewIdentity;
use Runa\Modules\Auth\Contracts\OperatorAuthority;
use Runa\Modules\Auth\Contracts\PreferenceScope;
use Runa\Modules\Auth\Contracts\PreferenceValue;
use Runa\Modules\Auth\Contracts\SessionSelection;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Queries\LookupIdentity;
use Runa\Modules\Auth\Support\EntryTransaction;
use Runa\Modules\Auth\Support\PolicyExceptions;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\ModuleHttp;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Api\PrincipalScope;
use Runa\Support\Modules\ModuleRegistry;
use Runa\Support\Modules\Settings\ModuleSettings;
use Runa\Support\Modules\Storage\ModuleStorage;
use Runa\Support\Modules\Updates\ModuleUpdates;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\Fixtures\CoreModuleStorage;

function entryLogin($test, array $payload): TestResponse
{
    $response = $test->postJson('/api/runa/auth/login', $payload);
    $test->withCredentials()->withCookie('runa_session', app('session')->getId());

    return $response;
}

function entryPolicy(array $patch): void
{
    $settings = app(ModuleSettings::class);
    $state = $settings->read('runa.auth');
    expect($settings->update('runa.auth', $patch, $state['revision'], $state['generation'])->status)->toBe('succeeded');
}

function entryUser(string $email, bool $password = true): User
{
    $identity = app(ChangeIdentity::class)->create(new NewIdentity($email, name: 'Test User', active: true, approved: true));
    $user = User::where('public_id', $identity->publicId)->first();
    if ($password) {
        DB::transaction(function () use ($user): void {
            $user->password = Hash::make('a-long-test-password');
            $user->save();
        });
    }

    return $user;
}

it('projects safe entry form options without exposing private settings or account state', function (): void {
    $response = $this->getJson('/api/runa/auth/entry-options')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    expect($response->json('data'))->toBe([
        'public_welcome_enabled' => true, 'public_registration' => false, 'identifier_mode' => 'email', 'remember_enabled' => false,
        'password' => ['min_length' => 12, 'max_bytes' => 72, 'mixed_case' => false, 'numbers' => false, 'symbols' => false],
    ]);
    entryPolicy(['public_registration' => true, 'session_remember_minutes' => 60, 'password_numbers' => true]);
    $this->getJson('/api/runa/auth/entry-options')->assertOk()->assertJsonPath('data.public_registration', true)
        ->assertJsonPath('data.remember_enabled', true)->assertJsonPath('data.password.numbers', true);
    entryUser('entry-options@example.test');
    entryLogin($this, ['identifier' => 'entry-options@example.test', 'password' => 'a-long-test-password'])->assertOk();
    $this->getJson('/api/runa/auth/security')->assertOk()->assertJsonPath('data.email_verified', false)
        ->assertJsonPath('data.recovery_codes_enabled', true)->assertJsonPath('data.password_change_factor', false);
});

it('registers exact named Auth entry pages without a wildcard or consuming GET token route', function (): void {
    foreach (['login', 'register', 'forgot-password', 'reset-password', 'invitation', 'verify-email', 'confirm-address', 'security'] as $page) {
        $route = app('router')->getRoutes()->match(Request::create('/auth/'.$page));
        expect($route->getName())->toBe('runa.auth.pages.'.str_replace('-', '_', $page));
    }
    expect(fn () => app('router')->getRoutes()->match(Request::create('/auth/unknown')))
        ->toThrow(NotFoundHttpException::class);
});

beforeEach(function (): void {
    $this->authSchemas = [];
    foreach (['runa_audit', 'runa_auth', 'runa_permissions'] as $schema) {
        if (DB::selectOne('SELECT 1 FROM pg_namespace WHERE nspname = ?', [$schema])) {
            throw new RuntimeException('Auth fixture requires absent disposable schemas.');
        }
    }
    $this->permissionsStorageAbsent = true;
    app(ModuleRegistry::class)->synchronize('runa');
    foreach (['audit' => 'events', 'auth' => 'identities'] as $code => $table) {
        $migration = require base_path('runa/modules/'.$code.'/database/migrations/2026_10_23_000001_runa_'.$code.'_'.$table.'.php');
        $migration->up();
        $this->authSchemas[] = 'runa_'.$code;
        DB::table('shared.modules')->where('module_id', 'runa.'.$code)->update(['installed_version' => app(ModuleRegistry::class)->snapshot()->modules['runa.'.$code]->sourceVersion]);
    }
    (require base_path('runa/modules/auth/database/migrations/2026_10_23_000002_runa_auth_entry_tokens.php'))->up();
    (require base_path('runa/modules/auth/database/migrations/2026_10_23_000003_runa_auth_sessions.php'))->up();
    (require base_path('runa/modules/auth/database/migrations/2026_10_23_000004_runa_auth_credentials.php'))->up();
    (require base_path('runa/modules/auth/database/migrations/2026_10_23_000005_runa_auth_profiles.php'))->up();
    (require base_path('runa/modules/auth/database/migrations/2026_10_23_000006_runa_auth_preferences.php'))->up();
    (require base_path('runa/modules/auth/database/migrations/2026_10_24_000007_runa_auth_policy.php'))->up();
    DB::transaction(fn () => app(PolicyHistory::class)->baseline(DB::connection()));
    DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    // Trusted test-only invariant participant. Real authority remains denied until Permissions.
    config()->set('queue.default', 'database');
    config()->set('hashing.bcrypt.rounds', 4);
    app()->bind(AuthorityGuard::class, fn () => new class implements AuthorityGuard
    {
        public function assertMayRemove(Connection $connection, array $publicIds): void
        {
            expect($connection)->toBe(DB::connection());
            expect($connection->transactionLevel())->toBeGreaterThan(0);
        }
    });
    app(ModuleHttp::class)->register();
    app('router')->getRoutes()->refreshNameLookups();
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
    if (isset($this->profileAvatarRoot)) {
        Storage::forgetDisk('runa_private');
        File::deleteDirectory($this->profileAvatarRoot);
    }
    while (DB::connection()->transactionLevel() > 0) {
        DB::rollBack();
    }
    $authId = DB::table('shared.modules')->where('module_id', 'runa.auth')->value('id');
    foreach (DB::table('shared.module_work')->where('module_id', $authId)->pluck('public_id') as $workId) {
        DB::table('shared.jobs')->whereRaw("payload::jsonb ->> 'runa_work' = ?", [$workId])->delete();
    }
    DB::table('shared.module_work')->where('module_id', $authId)->delete();
    if ($this->permissionsStorageAbsent ?? false) {
        CoreModuleStorage::removePermissions();
    }
    // Exact fixture-created objects; deliberately no CASCADE or schema-wide reset.
    foreach (array_reverse($this->authSchemas ?? []) as $schema) {
        foreach ($schema === 'runa_auth' ? ['operator_tokens', 'initialization', 'policy_exceptions', 'policy_versions', 'notice_scan', 'known_browsers', 'notice_frequencies', 'security_notices', 'delivery_preferences', 'preferences', 'profile_mail', 'email_changes', 'secondary_emails', 'avatar_files', 'step_up_proofs', 'password_history', 'login_windows', 'sessions', 'trusted_devices', 'entry_tokens', 'users'] : ['entities', 'events'] as $table) {
            DB::statement('DROP TABLE '.$schema.'.'.$table.' RESTRICT');
        }
        DB::statement('DROP SCHEMA '.$schema.' RESTRICT');
        $module = str_replace('runa_', 'runa.', $schema);
        $id = DB::table('shared.modules')->where('module_id', $module)->value('id');
        DB::table('shared.module_update_steps')->where('module_id', $id)->delete();
        DB::table('shared.modules')->where('module_id', $module)->delete();
    }
    DB::table('shared.module_registry_state')->where('id', 1)->update(['update_operation' => null, 'update_owner' => null, 'update_fingerprint' => null]);
    DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    DB::purge('auth_competitor');
    DB::purge('auth_entry_competitor');
});

it('uses guarded named Auth endpoints without Fortify bypasses and rotates cookie sessions on login and logout', function (): void {
    $user = entryUser('session@example.test');
    expect(app()->getProvider(FortifyServiceProvider::class))->toBeInstanceOf(FortifyServiceProvider::class);
    expect(Fortify::$registersRoutes)->toBeFalse();
    foreach (['login', 'register', 'forgot-password', 'two-factor-challenge'] as $path) {
        $this->postJson('/'.$path)->assertNotFound();
    }
    $csrf = $this->getJson('/api/runa/auth/csrf')->assertOk()->assertCookie('XSRF-TOKEN')->assertCookie('runa_session');
    $oldId = app('session')->getId();
    entryLogin($this, ['identifier' => 'session@example.test', 'password' => 'a-long-test-password'])
        ->assertOk()->assertJsonPath('data.public_id', $user->public_id)
        ->assertJsonPath('data.restrictions', ['email_verification_required']);
    expect(app('session')->getId())->not->toBe($oldId);
    $this->getJson('/api/runa/auth/session')->assertOk();
    $this->getJson('/api/identity')->assertForbidden()->assertJsonPath('error.code', 'account_restricted');
    $this->postJson('/api/runa/auth/logout')->assertOk();
    Auth::forgetGuards();
    $this->getJson('/api/runa/auth/session')->assertUnauthorized();
});

it('keeps registration opt-in generic and atomic with approval distinct from verification', function (): void {
    $payload = ['email' => 'register@example.test', 'name' => 'Registered', 'password' => 'a-long-test-password', 'password_confirmation' => 'a-long-test-password'];
    $this->postJson('/api/runa/auth/register', $payload)->assertForbidden();
    entryPolicy(['public_registration' => true]);
    $first = $this->postJson('/api/runa/auth/register', $payload)->assertAccepted();
    $this->postJson('/api/runa/auth/register', $payload)->assertAccepted()->assertExactJson($first->json());
    expect(User::count())->toBe(1)->and(Auth::guard('web')->check())->toBeFalse()
        ->and(DB::table('runa_auth.entry_tokens')->count())->toBe(1)->and(DB::table('shared.module_work')->count())->toBe(1);
    $serialized = DB::table('runa_audit.events')->get()->toJson();
    expect($serialized)->not->toContain('register@example.test', 'a-long-test-password');
    entryPolicy(['registration_approval' => 'administrator']);
    $payload['email'] = 'pending@example.test';
    $this->postJson('/api/runa/auth/register', $payload)->assertAccepted();
    expect(User::where('email', 'pending@example.test')->first()->approved)->toBeFalse()
        ->and(DB::table('runa_auth.entry_tokens')->count())->toBe(1);
    entryLogin($this, ['identifier' => 'pending@example.test', 'password' => 'a-long-test-password'])
        ->assertUnprocessable()->assertJsonPath('error.code', 'invalid_credentials');
});

it('consumes invitation and verification tokens exactly once and fails closed when Audit cannot persist', function (): void {
    $user = entryUser('invite@example.test', false);
    $tokens = app(EntryTokens::class);
    expect($tokens->invite($user->public_id, $user->version))->toBeTrue();
    $row = DB::table('runa_auth.entry_tokens')->latest('id')->first();
    $secret = Crypt::decryptString($row->delivery_secret);
    expect($row->token_hash)->toBe(hash('sha256', $secret))->and(DB::table('shared.jobs')->value('payload'))->not->toContain($secret);
    DB::statement('ALTER TABLE runa_audit.events ADD CONSTRAINT entry_fixture_refusal CHECK (false) NOT VALID');
    expect(fn () => $tokens->consume($secret, 'invitation', 'new-long-password'))->toThrow(AuditUnavailable::class);
    expect(User::find($user->id)->password)->toBeNull()->and(DB::table('runa_auth.entry_tokens')->where('id', $row->id)->value('consumed_at'))->toBeNull();
    DB::statement('ALTER TABLE runa_audit.events DROP CONSTRAINT entry_fixture_refusal');
    $this->postJson('/api/runa/auth/invitations/accept', ['token' => $secret, 'password' => 'new-long-password', 'password_confirmation' => 'new-long-password'])->assertOk();
    expect(Hash::check('new-long-password', User::find($user->id)->password))->toBeTrue()
        ->and(User::find($user->id)->password_change_required)->toBeFalse()->and(Auth::guard('web')->check())->toBeFalse();
    expect(fn () => $tokens->consume($secret, 'invitation', 'new-long-password'))->toThrow(OperationRejected::class);
    $verified = entryUser('verify@example.test');
    $tokens->resendVerification($verified->public_id);
    $row = DB::table('runa_auth.entry_tokens')->latest('id')->first();
    $secret = Crypt::decryptString($row->delivery_secret);
    $this->postJson('/api/runa/auth/verification/complete', ['token' => $secret])->assertOk();
    expect(User::find($verified->id)->email_verified_at)->not->toBeNull();
    $this->postJson('/api/runa/auth/verification/complete', ['token' => $secret])->assertUnprocessable();
});

it('rechecks activation locks token invalidation and expiry without revealing them to anonymous login', function (): void {
    $user = entryUser('scheduled@example.test');
    app(ChangeIdentity::class)->change($user->public_id, 1, new LifecycleChange('schedule_activation', now()->addHour()->utc()->format('Y-m-d\TH:i:s\Z')));
    $bad = entryLogin($this, ['identifier' => $user->email, 'password' => 'a-long-test-password'])->assertUnprocessable()->json();
    entryLogin($this, ['identifier' => 'absent@example.test', 'password' => 'a-long-test-password'])->assertUnprocessable()->assertExactJson($bad);
    $user = entryUser('rotate@example.test', false);
    $tokens = app(EntryTokens::class);
    $tokens->invite($user->public_id, 1);
    $one = DB::table('runa_auth.entry_tokens')->latest('id')->first();
    $secret = Crypt::decryptString($one->delivery_secret);
    $tokens->invite($user->public_id, 1);
    expect(fn () => $tokens->consume($secret, 'invitation', 'new-long-password'))->toThrow(OperationRejected::class);
    $two = DB::table('runa_auth.entry_tokens')->latest('id')->first();
    $secret = Crypt::decryptString($two->delivery_secret);
    CarbonImmutable::setTestNow(CarbonImmutable::parse($two->expires_at));
    expect(fn () => $tokens->consume($secret, 'invitation', 'new-long-password'))->toThrow(OperationRejected::class);
});

it('delivers each token once and suppresses actionable stale account mail and bounded reminders', function (): void {
    $user = entryUser('mail@example.test', false);
    entryPolicy(['invitation_reminder_minutes' => 5, 'invitation_reminder_count' => 1]);
    $tokens = app(EntryTokens::class);
    $tokens->invite($user->public_id, 1);
    $row = DB::table('runa_auth.entry_tokens')->latest('id')->first();
    $delivery = app(DeliverEntryMail::class);
    $delivery->deliver($row->public_id);
    $delivery->deliver($row->public_id);
    expect(app('mailer')->getSymfonyTransport()->messages())->toHaveCount(1);
    expect(DB::table('runa_auth.entry_tokens')->where('id', $row->id)->value('delivery_secret'))->toBeNull();
    CarbonImmutable::setTestNow(CarbonImmutable::now('UTC')->addMinutes(6));
    $tokens->remind();
    $tokens->remind();
    expect(DB::table('runa_auth.entry_tokens')->count())->toBe(2);
    $reminder = DB::table('runa_auth.entry_tokens')->latest('id')->first();
    app(ChangeIdentity::class)->change($user->public_id, 1, new LifecycleChange('deactivate'));
    $delivery->deliver($reminder->public_id);
    expect(app('mailer')->getSymfonyTransport()->messages())->toHaveCount(1);
    expect(app(ModuleApis::class)->resolve('runa.auth', AuthInvitations::class, '1.0.0'))
        ->toBeInstanceOf(AuthInvitations::class);
    expect(fn () => app(ModuleApis::class)->resolve('runa.auth', AuthInvitations::class, '1.0.0')->invite($user->public_id, 2))->toThrow(OperationRejected::class);
});

it('rejects a missing CSRF proof with the actual middleware even in the focused test harness', function (): void {
    app()->bind(ValidateCsrfToken::class, fn ($app) => new class($app, $app['encrypter']) extends ValidateCsrfToken
    {
        protected function runningUnitTests()
        {
            return false;
        }
    });
    entryLogin($this, ['identifier' => 'x@example.test', 'password' => 'no-password'])
        ->assertStatus(419)->assertJsonPath('error.code', 'csrf_token_mismatch');
});

it('upgrades encrypted identity settings additively only inside the reserved updater', function (): void {
    // Establish real migration/step history, then model the supported prior data version.
    foreach (['runa_auth.operator_tokens', 'runa_auth.initialization', 'runa_auth.policy_exceptions', 'runa_auth.policy_versions', 'runa_auth.notice_scan', 'runa_auth.known_browsers', 'runa_auth.notice_frequencies', 'runa_auth.security_notices', 'runa_auth.delivery_preferences', 'runa_auth.preferences', 'runa_auth.profile_mail', 'runa_auth.email_changes', 'runa_auth.secondary_emails', 'runa_auth.avatar_files', 'runa_auth.step_up_proofs', 'runa_auth.password_history', 'runa_auth.login_windows', 'runa_auth.sessions', 'runa_auth.trusted_devices', 'runa_auth.entry_tokens', 'runa_auth.users', 'runa_audit.entities', 'runa_audit.events'] as $table) {
        DB::statement('DROP TABLE '.$table.' RESTRICT');
    }
    DB::statement('DROP SCHEMA runa_auth RESTRICT');
    DB::statement('DROP SCHEMA runa_audit RESTRICT');
    DB::table('shared.modules')->whereIn('module_id', ['runa.auth', 'runa.audit'])->update(['installed_version' => null]);
    app(ModuleUpdates::class)->apply('runa', str_repeat('b', 40), static function (): void {});
    $authId = DB::table('shared.modules')->where('module_id', 'runa.auth')->value('id');
    DB::table('shared.module_update_steps')->where('module_id', $authId)->whereIn('step', ['upgrade:entry:0.1.0:0.2.0', 'upgrade:sessions:0.2.0:0.3.0', 'upgrade:credentials:0.3.0:0.4.0', 'upgrade:profiles:0.4.0:0.5.0', 'upgrade:preferences:0.5.0:0.6.0', 'upgrade:policy:0.6.0:0.7.0', 'upgrade:entry_ui:0.7.0:0.8.0', 'upgrade:account_ui:0.8.0:0.9.0', 'upgrade:permission_declarations:0.9.0:0.10.0'])->delete();
    $old = ['identifier_mode' => 'either', 'reuse_deleted_identifiers' => true];
    DB::table('shared.modules')->where('module_id', 'runa.auth')->update(['installed_version' => '0.1.0', 'settings_revision' => 4,
        'settings_values' => app('encrypter')->encrypt(json_encode($old, JSON_THROW_ON_ERROR), false)]);
    $updates = app(ModuleUpdates::class);
    $updates->apply('runa', str_repeat('b', 40), static function (): void {});
    $state = app(ModuleSettings::class)->read('runa.auth');
    expect($state['settings']['identifier_mode']['value'])->toBe('either')
        ->and($state['settings']['public_registration']['value'])->toBeFalse()
        ->and($state['settings']['public_welcome_enabled']['value'])->toBeTrue()->and($state['revision'])->toBe(5);
    $events = DB::table('runa_audit.events')->where('action', 'settings.upgrade')->count();
    expect($events)->toBe(1);
    $updates->apply('runa', str_repeat('b', 40), static function (): void {});
    expect(app(ModuleSettings::class)->read('runa.auth')['revision'])->toBe(5);
});

it('arbitrates invitation consumption against another connection holding the current user lock', function (): void {
    $user = entryUser('race@example.test', false);
    $tokens = app(EntryTokens::class);
    $tokens->invite($user->public_id, 1);
    $row = DB::table('runa_auth.entry_tokens')->latest('id')->first();
    $secret = Crypt::decryptString($row->delivery_secret);
    config()->set('database.connections.auth_entry_competitor', config('database.connections.pgsql'));
    $other = DB::connection('auth_entry_competitor');
    $other->beginTransaction();
    try {
        $other->table('runa_auth.users')->where('id', $user->id)->lockForUpdate()->first();
        DB::statement("SET lock_timeout = '100ms'");
        expect(fn () => $tokens->consume($secret, 'invitation', 'new-long-password'))->toThrow(QueryException::class, 'lock timeout');
        expect(DB::table('runa_auth.entry_tokens')->where('id', $row->id)->value('consumed_at'))->toBeNull();
    } finally {
        DB::statement('RESET lock_timeout');
        $other->rollBack();
    }
    $tokens->consume($secret, 'invitation', 'new-long-password');
    expect(fn () => $tokens->consume($secret, 'invitation', 'new-long-password'))->toThrow(OperationRejected::class);
});

it('enforces tracked session deadlines without treating polling as activity and binds heartbeats to the original absolute deadline', function (): void {
    CarbonImmutable::setTestNow('2026-10-23 12:00:00 UTC');
    entryPolicy(['session_idle_minutes' => 2, 'session_absolute_minutes' => 4, 'session_heartbeat_seconds' => 30]);
    $user = entryUser('activity@example.test');
    entryLogin($this, ['identifier' => $user->email, 'password' => 'a-long-test-password'])->assertOk();
    $row = DB::table('runa_auth.sessions')->sole();
    $this->getJson('/api/runa/auth/sessions')->assertOk()->assertJsonPath('data.0.current', true);
    CarbonImmutable::setTestNow('2026-10-23 12:01:00 UTC');
    $this->getJson('/api/runa/auth/session')->assertOk();
    expect(DB::table('runa_auth.sessions')->value('activity_at'))->toBe($row->activity_at);
    $this->postJson('/api/runa/auth/session/activity', ['activity' => 'mousemove'])->assertUnprocessable();
    $this->postJson('/api/runa/auth/session/activity', ['activity' => 'typing'])->assertOk();
    $updated = DB::table('runa_auth.sessions')->value('activity_at');
    CarbonImmutable::setTestNow('2026-10-23 12:01:10 UTC');
    $this->postJson('/api/runa/auth/session/activity', ['activity' => 'touch'])->assertOk();
    expect(DB::table('runa_auth.sessions')->value('activity_at'))->toBe($updated);
    CarbonImmutable::setTestNow('2026-10-23 12:02:50 UTC');
    $this->postJson('/api/runa/auth/session/activity', ['activity' => 'scroll'])->assertOk();
    CarbonImmutable::setTestNow('2026-10-23 12:03:50 UTC');
    $this->postJson('/api/runa/auth/session/activity', ['activity' => 'click'])->assertOk();
    expect(DB::table('runa_auth.sessions')->value('expires_at'))->toBe($row->expires_at);
    CarbonImmutable::setTestNow('2026-10-23 12:04:00 UTC');
    $this->getJson('/api/runa/auth/session')->assertUnauthorized()->assertJsonPath('error.code', 'authentication_required');
});

it('atomically evicts oldest sessions and refuses stale or foreign session proofs while revocation rolls back with Audit', function (): void {
    entryPolicy(['session_limit' => 1]);
    $user = entryUser('cap@example.test');
    $other = entryUser('foreign@example.test');
    $login = ['identifier' => $user->email, 'password' => 'a-long-test-password'];
    entryLogin($this, $login)->assertOk();
    $old = DB::table('runa_auth.sessions')->sole();
    $oldCookie = clone app('session')->driver();
    entryLogin($this, $login)->assertOk();
    expect(DB::table('runa_auth.sessions')->where('id', $old->id)->value('revoked_at'))->not->toBeNull();
    $current = DB::table('runa_auth.sessions')->latest('id')->first();
    expect(app(ManageSessions::class)->revoke($other->public_id, $current->public_id))->toBeFalse();
    DB::statement('ALTER TABLE runa_audit.events ADD CONSTRAINT session_fixture_refusal CHECK (false) NOT VALID');
    expect(fn () => app(ManageSessions::class)->revoke($user->public_id, $current->public_id))->toThrow(AuditUnavailable::class);
    expect(DB::table('runa_auth.sessions')->where('id', $current->id)->value('revoked_at'))->toBeNull();
    DB::statement('ALTER TABLE runa_audit.events DROP CONSTRAINT session_fixture_refusal');
    $this->postJson('/api/runa/auth/sessions/revoke', ['public_id' => $current->public_id])->assertOk();
    $this->getJson('/api/runa/auth/session')->assertUnauthorized();
    $request = Request::create('/');
    $request->setLaravelSession($oldCookie);
    expect(app(SessionStore::class)->current($request, $user))->toBeNull();
});

it('keeps remembered sessions revocable and bounded and cleans only expired operational rows', function (): void {
    CarbonImmutable::setTestNow('2026-10-23 12:00:00 UTC');
    entryPolicy(['session_remember_minutes' => 3, 'session_idle_minutes' => 10]);
    $user = entryUser('remember@example.test');
    $response = entryLogin($this, ['identifier' => $user->email, 'password' => 'a-long-test-password', 'remember' => true])->assertOk();
    expect(DB::table('runa_auth.sessions')->value('remembered'))->toBeTrue();
    $cookies = $response->headers->getCookies();
    expect(collect($cookies)->first(fn ($cookie) => $cookie->getName() === 'runa_session')->getExpiresTime())->toBeGreaterThan(0);
    CarbonImmutable::setTestNow('2026-10-23 12:03:00 UTC');
    $this->getJson('/api/runa/auth/session')->assertUnauthorized();
    $auditCount = DB::table('runa_audit.events')->count();
    app(CleanupSessions::class)->run();
    expect(DB::table('runa_auth.sessions')->count())->toBe(0)->and(DB::table('runa_audit.events')->count())->toBe($auditCount);
    entryLogin($this, ['identifier' => $user->email, 'password' => 'a-long-test-password'])->assertOk();
    app(ChangeIdentity::class)->change($user->public_id, 1, new LifecycleChange('deactivate'));
    expect(DB::table('runa_auth.sessions')->whereNull('revoked_at')->count())->toBe(0);
    $this->getJson('/api/runa/auth/session')->assertUnauthorized();
});

it('bounds account and source abuse locks without extending them or exposing account existence', function (): void {
    CarbonImmutable::setTestNow('2026-10-23 12:00:00 UTC');
    entryPolicy(['abuse_account_limit' => 2, 'abuse_source_limit' => 20, 'abuse_lock_seconds' => 60, 'abuse_window_seconds' => 30, 'abuse_escalation_limit' => 2]);
    $user = entryUser('abuse@example.test');
    $bad = ['identifier' => $user->email, 'password' => 'wrong-password'];
    $shape = entryLogin($this, $bad)->assertUnprocessable()->json();
    entryLogin($this, $bad)->assertUnprocessable()->assertExactJson($shape);
    $locked = DB::table('runa_auth.login_windows')->whereNotNull('locked_until')->value('locked_until');
    CarbonImmutable::setTestNow('2026-10-23 12:00:30 UTC');
    entryLogin($this, ['identifier' => $user->email, 'password' => 'a-long-test-password'])->assertUnprocessable()->assertExactJson($shape);
    entryLogin($this, ['identifier' => 'absent@example.test', 'password' => 'wrong-password'])->assertUnprocessable()->assertExactJson($shape);
    expect(DB::table('runa_auth.login_windows')->whereNotNull('locked_until')->value('locked_until'))->toBe($locked)
        ->and(User::find($user->id)->security_locked)->toBeFalse()
        ->and(DB::table('runa_audit.events')->where('action', 'login.abuse_escalated')->count())->toBe(1);
    CarbonImmutable::setTestNow('2026-10-23 12:01:01 UTC');
    entryLogin($this, ['identifier' => $user->email, 'password' => 'a-long-test-password'])->assertOk();
    expect(DB::table('runa_auth.login_windows')->get()->toJson())->not->toContain($user->email, '127.0.0.1');
});

it('serializes session admission against another worker holding the registry lock', function (): void {
    $user = entryUser('concurrency@example.test');
    config()->set('database.connections.auth_entry_competitor', config('database.connections.pgsql'));
    $other = DB::connection('auth_entry_competitor');
    $other->beginTransaction();
    try {
        $other->table('shared.module_registry_state')->where('id', 1)->lockForUpdate()->first();
        DB::statement("SET lock_timeout = '100ms'");
        $request = Request::create('/', 'POST', ['identifier' => $user->email, 'password' => 'a-long-test-password']);
        $request->setLaravelSession(app('session')->driver());
        expect(fn () => app(AuthenticateSession::class)->login($request))->toThrow(QueryException::class, 'lock timeout');
        expect(DB::table('runa_auth.sessions')->count())->toBe(0);
    } finally {
        DB::statement('RESET lock_timeout');
        $other->rollBack();
    }
});

it('rolls back session admission and eviction together when login Audit fails', function (): void {
    entryPolicy(['session_limit' => 1]);
    $user = entryUser('admission-rollback@example.test');
    $payload = ['identifier' => $user->email, 'password' => 'a-long-test-password'];
    entryLogin($this, $payload)->assertOk();
    DB::statement('ALTER TABLE runa_audit.events ADD CONSTRAINT session_fixture_refusal CHECK (false) NOT VALID');
    $request = Request::create('/', 'POST', $payload);
    $request->setLaravelSession(app('session')->driver());
    expect(fn () => app(AuthenticateSession::class)->login($request))->toThrow(AuditUnavailable::class);
    expect(DB::table('runa_auth.sessions')->count())->toBe(1)
        ->and(DB::table('runa_auth.sessions')->value('revoked_at'))->toBeNull()
        ->and(Auth::guard('web')->check())->toBeFalse();
});

it('counts successful logins in source windows and restarts after the fixed lock deadline', function (): void {
    CarbonImmutable::setTestNow('2026-10-23 12:00:00 UTC');
    entryPolicy(['abuse_source_limit' => 1, 'abuse_window_seconds' => 30, 'abuse_lock_seconds' => 60]);
    $user = entryUser('source-window@example.test');
    $payload = ['identifier' => $user->email, 'password' => 'a-long-test-password'];
    entryLogin($this, $payload)->assertOk();
    entryLogin($this, $payload)->assertUnprocessable()->assertJsonPath('error.code', 'invalid_credentials');
    expect(DB::table('runa_auth.sessions')->count())->toBe(1);
    CarbonImmutable::setTestNow('2026-10-23 12:01:01 UTC');
    entryLogin($this, $payload)->assertOk();
    expect(DB::table('runa_auth.sessions')->count())->toBe(2);
});

it('caps remembered authentication by the general absolute policy at admission and on policy tightening', function (): void {
    CarbonImmutable::setTestNow('2026-10-23 12:00:00 UTC');
    entryPolicy(['session_remember_minutes' => 60, 'session_absolute_minutes' => 2, 'session_idle_minutes' => 10]);
    $user = entryUser('absolute-remember@example.test');
    $payload = ['identifier' => $user->email, 'password' => 'a-long-test-password', 'remember' => true];
    entryLogin($this, $payload)->assertOk();
    expect(CarbonImmutable::parse(DB::table('runa_auth.sessions')->value('expires_at'))->toIso8601String())->toBe('2026-10-23T12:02:00+00:00');
    CarbonImmutable::setTestNow('2026-10-23 12:02:00 UTC');
    $this->getJson('/api/runa/auth/session')->assertUnauthorized();
    entryPolicy(['session_absolute_minutes' => 10]);
    entryLogin($this, $payload)->assertOk();
    CarbonImmutable::setTestNow('2026-10-23 12:04:00 UTC');
    entryPolicy(['session_absolute_minutes' => 2]);
    $this->getJson('/api/runa/auth/session')->assertUnauthorized();
});

function credentialRequest(string $path, array $payload = []): Request
{
    $request = Request::create($path, 'POST', $payload);
    $request->setLaravelSession(app('session')->driver());

    return $request;
}

function credentialEnroll($test, User $user): array
{
    $begin = $test->postJson('/api/runa/auth/security/totp-begin', ['current_password' => 'a-long-test-password'])->assertOk()->json('data');
    $counter = intdiv(CarbonImmutable::now('UTC')->getTimestamp(), 30);
    $code = app(Google2FA::class)->oathTotp($begin['secret'], $counter);
    $codes = $test->postJson('/api/runa/auth/security/totp-confirm', ['current_password' => 'a-long-test-password', 'enrollment_code' => $code])
        ->assertOk()->json('data.recovery_codes');
    expect($codes)->toHaveCount(8);

    return [$begin['secret'], $codes];
}

it('enforces mandatory TOTP enrollment and transactional replay protection with single use recovery', function (): void {
    CarbonImmutable::setTestNow('2026-10-23 12:00:00 UTC');
    entryPolicy(['two_factor_required' => 'totp', 'email_otp_enabled' => true]);
    $user = entryUser('mandatory-factor@example.test');
    DB::transaction(function () use ($user): void {
        $user->email_verified_at = now();
        $user->save();
    });
    entryLogin($this, ['identifier' => $user->email, 'password' => 'a-long-test-password'])->assertOk()
        ->assertJsonPath('data.restrictions', ['two_factor_enrollment_required']);
    $this->postJson('/api/runa/auth/password/change', ['current_password' => 'a-long-test-password'])->assertForbidden();
    [$secret, $codes] = credentialEnroll($this, $user);
    $stored = User::find($user->id);
    expect($stored->totp_secret)->not->toBe($secret)->and($stored->recovery_hashes)->not->toContain($codes[0]);
    entryLogin($this, ['identifier' => $user->email, 'password' => 'a-long-test-password'])->assertOk()->assertJsonPath('data.restrictions', ['two_factor_required']);
    $oldCode = app(Google2FA::class)->oathTotp($secret, intdiv(CarbonImmutable::now('UTC')->getTimestamp(), 30));
    $this->postJson('/api/runa/auth/security/challenge', ['method' => 'totp', 'code' => $oldCode])->assertUnprocessable();
    $this->postJson('/api/runa/auth/security/challenge', ['method' => 'email', 'code' => '123456'])->assertUnprocessable();
    $this->postJson('/api/runa/auth/security/challenge', ['method' => 'recovery', 'code' => $codes[0]])->assertOk();
    $this->postJson('/api/runa/auth/security/challenge', ['method' => 'recovery', 'code' => $codes[0]])->assertUnprocessable();
    $this->getJson('/api/runa/auth/session')->assertOk()->assertJsonPath('data.restrictions', []);
    expect(DB::table('runa_audit.events')->get()->toJson())->not->toContain($secret, $codes[0]);
});

it('rolls back factor enrollment and OTP consumption when synchronous Audit refuses', function (): void {
    $user = entryUser('factor-audit@example.test');
    DB::transaction(function () use ($user): void {
        $user->email_verified_at = now();
        $user->save();
    });
    entryLogin($this, ['identifier' => $user->email, 'password' => 'a-long-test-password'])->assertOk();
    [$secret, $codes] = credentialEnroll($this, $user);
    DB::statement('ALTER TABLE runa_audit.events ADD CONSTRAINT factor_fixture_refusal CHECK (false) NOT VALID');
    $request = credentialRequest('/', ['method' => 'recovery', 'code' => $codes[0]]);
    expect(fn () => app(CredentialFactors::class)->handle($request, 'challenge'))->toThrow(AuditUnavailable::class);
    expect(User::find($user->id)->recovery_hashes)->toContain(hash('sha256', $codes[0]));
    DB::statement('ALTER TABLE runa_audit.events DROP CONSTRAINT factor_fixture_refusal');
    $this->postJson('/api/runa/auth/security/challenge', ['method' => 'recovery', 'code' => $codes[0]])->assertOk();
});

it('requires current password and history and applies explicit password session policies atomically', function (): void {
    $user = entryUser('password-policy@example.test');
    entryLogin($this, ['identifier' => $user->email, 'password' => 'a-long-test-password'])->assertOk();
    entryLogin($this, ['identifier' => $user->email, 'password' => 'a-long-test-password'])->assertOk();
    $payload = ['current_password' => 'wrong', 'password' => 'a-new-long-password', 'password_confirmation' => 'a-new-long-password'];
    $this->postJson('/api/runa/auth/password/change', $payload)->assertUnprocessable();
    $payload['current_password'] = 'a-long-test-password';
    $this->postJson('/api/runa/auth/password/change', $payload)->assertOk();
    expect(DB::table('runa_auth.sessions')->whereNull('revoked_at')->count())->toBe(1)
        ->and(DB::table('runa_auth.password_history')->count())->toBe(1);
    $this->postJson('/api/runa/auth/password/change', ['current_password' => 'a-new-long-password', 'password' => 'a-long-test-password', 'password_confirmation' => 'a-long-test-password'])->assertUnprocessable();
    expect(Hash::check('a-new-long-password', User::find($user->id)->password))->toBeTrue();
    DB::statement('ALTER TABLE runa_audit.events ADD CONSTRAINT password_fixture_refusal CHECK (false) NOT VALID');
    $request = credentialRequest('/', ['current_password' => 'a-new-long-password', 'password' => 'another-long-password', 'sessions' => 'revoke_all']);
    expect(fn () => app(Passwords::class)->change($request))->toThrow(AuditUnavailable::class);
    expect(Hash::check('a-new-long-password', User::find($user->id)->password))->toBeTrue()
        ->and(DB::table('runa_auth.sessions')->whereNull('revoked_at')->count())->toBe(1);
});

it('keeps reset links single use and mandatory TOTP intact without authenticating recovery', function (): void {
    entryPolicy(['two_factor_required' => 'totp']);
    $user = entryUser('password-reset@example.test');
    DB::transaction(function () use ($user): void {
        $user->email_verified_at = now();
        $user->save();
    });
    entryLogin($this, ['identifier' => $user->email, 'password' => 'a-long-test-password'])->assertOk();
    [$secret, $codes] = credentialEnroll($this, $user);
    $generic = $this->postJson('/api/runa/auth/password/forgot', ['identifier' => $user->email])->assertAccepted()->json();
    $this->postJson('/api/runa/auth/password/forgot', ['identifier' => 'absent@example.test'])->assertAccepted()->assertExactJson($generic);
    $token = DB::table('runa_auth.entry_tokens')->where('purpose', 'reset')->first();
    $resetSecret = Crypt::decryptString($token->delivery_secret);
    $payload = ['token' => $resetSecret, 'password' => 'a-new-reset-password', 'password_confirmation' => 'a-new-reset-password'];
    $this->postJson('/api/runa/auth/password/reset', $payload)->assertOk();
    $this->postJson('/api/runa/auth/password/reset', $payload)->assertUnprocessable();
    $this->getJson('/api/runa/auth/session')->assertUnauthorized();
    entryLogin($this, ['identifier' => $user->email, 'password' => 'a-new-reset-password'])->assertOk()->assertJsonPath('data.restrictions', ['two_factor_required']);
    expect(Crypt::decryptString(User::find($user->id)->totp_secret))->toBe($secret);
});

it('binds step up to identity session action and consumes every action proof inside the business transaction', function (): void {
    $user = entryUser('step-up@example.test');
    DB::transaction(function () use ($user): void {
        $user->email_verified_at = now();
        $user->save();
    });
    entryLogin($this, ['identifier' => $user->email, 'password' => 'a-long-test-password'])->assertOk();
    $this->postJson('/api/runa/auth/step-up', ['action' => 'app.invoice.publish', 'requirement' => 'password', 'current_password' => 'a-long-test-password'])->assertOk();
    $request = credentialRequest('/');
    app()->instance('request', $request);
    $scope = app(PrincipalScope::class);
    $api = app(ModuleApis::class)->resolve('runa.auth', AuthStepUp::class, '1.0.0');
    $assert = fn ($action, $requirement, $every = false) => DB::transaction(fn () => $scope->run(new TrustedPrincipal($user->public_id), fn () => $api->assertVerified($action, $requirement, everyAction: $every)));
    expect(fn () => $assert('app.invoice.delete', 'password'))->toThrow(OperationRejected::class);
    expect(fn () => $assert('app.invoice.publish', 'both'))->toThrow(OperationRejected::class);
    expect($assert('app.invoice.publish', 'password'))->toBeTrue();
    // A rollback returns consumption together with the business mutation.
    try {
        DB::transaction(function () use ($assert): void {
            $assert('app.invoice.publish', 'password', true);
            throw new RuntimeException('business rollback');
        });
    } catch (RuntimeException $error) {
        expect($error->getMessage())->toBe('business rollback');
    }
    expect($assert('app.invoice.publish', 'password', true))->toBeTrue();
    expect(fn () => $assert('app.invoice.publish', 'password', true))->toThrow(OperationRejected::class);
});

it('never uses trusted device bypass for mandatory step up and revokes trust on password change', function (): void {
    entryPolicy(['trusted_devices_enabled' => true]);
    $user = entryUser('trust@example.test');
    DB::transaction(function () use ($user): void {
        $user->email_verified_at = now();
        $user->save();
    });
    entryLogin($this, ['identifier' => $user->email, 'password' => 'a-long-test-password'])->assertOk();
    [$secret, $codes] = credentialEnroll($this, $user);
    $issued = $this->postJson('/api/runa/auth/trusted-devices', ['method' => 'recovery', 'code' => $codes[0]])->assertOk();
    $cookie = collect($issued->headers->getCookies())->first(fn ($cookie) => $cookie->getName() === 'runa_trust');
    expect($cookie->isHttpOnly())->toBeTrue();
    $this->withUnencryptedCookie('runa_trust', $cookie->getValue());
    entryLogin($this, ['identifier' => $user->email, 'password' => 'a-long-test-password'])->assertOk()->assertJsonPath('data.restrictions', []);
    $device = DB::table('runa_auth.trusted_devices')->first();
    expect($device->token_hash)->toHaveLength(64);
    $this->postJson('/api/runa/auth/step-up', ['action' => 'app.invoice.publish', 'requirement' => 'second_factor'])->assertUnprocessable();
    $this->postJson('/api/runa/auth/password/change', ['current_password' => 'a-long-test-password', 'password' => 'a-different-password', 'password_confirmation' => 'a-different-password', 'sessions' => 'keep_all'])->assertOk();
    expect(DB::table('runa_auth.trusted_devices')->value('revoked_at'))->not->toBeNull();
});

it('bounds email OTP attempts and enforces session binding expiry and one time use', function (): void {
    CarbonImmutable::setTestNow('2026-10-23 12:00:00 UTC');
    entryPolicy(['email_otp_enabled' => true]);
    $user = entryUser('email-otp@example.test');
    DB::transaction(function () use ($user): void {
        $user->email_verified_at = now();
        $user->save();
    });
    entryLogin($this, ['identifier' => $user->email, 'password' => 'a-long-test-password'])->assertOk();
    $this->postJson('/api/runa/auth/security/email-enable', ['current_password' => 'a-long-test-password'])->assertOk();
    $this->postJson('/api/runa/auth/security/email-send')->assertOk();
    $token = DB::table('runa_auth.entry_tokens')->where('purpose', 'email_otp')->latest('id')->first();
    $code = Crypt::decryptString($token->delivery_secret);
    expect($token->token_hash)->not->toBe(hash('sha256', $code));
    entryLogin($this, ['identifier' => $user->email, 'password' => 'a-long-test-password'])->assertOk();
    $this->postJson('/api/runa/auth/security/challenge', ['method' => 'email', 'code' => $code])->assertUnprocessable();
    $this->postJson('/api/runa/auth/security/email-send')->assertOk();
    $token = DB::table('runa_auth.entry_tokens')->where('purpose', 'email_otp')->latest('id')->first();
    $code = Crypt::decryptString($token->delivery_secret);
    CarbonImmutable::setTestNow('2026-10-23 12:05:00 UTC');
    $this->postJson('/api/runa/auth/security/challenge', ['method' => 'email', 'code' => $code])->assertUnprocessable();
    $this->postJson('/api/runa/auth/security/email-send')->assertOk();
    $token = DB::table('runa_auth.entry_tokens')->where('purpose', 'email_otp')->latest('id')->first();
    $code = Crypt::decryptString($token->delivery_secret);
    $this->postJson('/api/runa/auth/security/challenge', ['method' => 'email', 'code' => $code])->assertOk();
    $this->postJson('/api/runa/auth/security/challenge', ['method' => 'email', 'code' => $code])->assertUnprocessable();
    entryPolicy(['security_attempt_limit' => 1]);
    $this->postJson('/api/runa/auth/security/email-send')->assertUnprocessable();
});

it('enforces password age on ingress and current complexity on newly chosen passwords', function (): void {
    CarbonImmutable::setTestNow('2026-10-23 12:00:00 UTC');
    entryPolicy(['password_expiry_days' => 1, 'password_mixed_case' => true, 'password_numbers' => true]);
    $user = entryUser('password-age@example.test');
    DB::transaction(function () use ($user): void {
        $user->password_changed_at = CarbonImmutable::now('UTC')->subDays(1);
        $user->save();
    });
    entryLogin($this, ['identifier' => $user->email, 'password' => 'a-long-test-password'])->assertOk()->assertJsonPath('data.restrictions', ['email_verification_required', 'password_change_required']);
    $payload = ['current_password' => 'a-long-test-password', 'password' => 'a-simple-password', 'password_confirmation' => 'a-simple-password'];
    $this->postJson('/api/runa/auth/password/change', $payload)->assertUnprocessable();
    $payload['password'] = $payload['password_confirmation'] = 'A-new-password-123';
    $this->postJson('/api/runa/auth/password/change', $payload)->assertOk();
    $this->getJson('/api/runa/auth/session')->assertOk()->assertJsonPath('data.restrictions', ['email_verification_required']);
});

it('expires step up at the captured TTL and refuses a different trusted principal', function (): void {
    CarbonImmutable::setTestNow('2026-10-23 12:00:00 UTC');
    $user = entryUser('proof-ttl@example.test');
    $other = entryUser('proof-other@example.test');
    DB::transaction(function () use ($user): void {
        $user->email_verified_at = now();
        $user->save();
    });
    entryLogin($this, ['identifier' => $user->email, 'password' => 'a-long-test-password'])->assertOk();
    $this->postJson('/api/runa/auth/step-up', ['action' => 'app.invoice.publish', 'requirement' => 'password', 'current_password' => 'a-long-test-password'])->assertOk();
    app()->instance('request', credentialRequest('/'));
    $proofs = app(StepUp::class);
    $scope = app(PrincipalScope::class);
    $assert = fn ($id) => DB::transaction(fn () => $scope->run(new TrustedPrincipal($id), fn () => $proofs->assertVerified('app.invoice.publish', 'password')));
    expect(fn () => $assert($other->public_id))->toThrow(OperationRejected::class);
    expect($assert($user->public_id))->toBeTrue();
    CarbonImmutable::setTestNow('2026-10-23 12:05:00 UTC');
    expect(fn () => $assert($user->public_id))->toThrow(OperationRejected::class);
});

it('serializes security proof consumption against another worker holding the account lock', function (): void {
    $user = entryUser('security-race@example.test');
    DB::transaction(function () use ($user): void {
        $user->email_verified_at = now();
        $user->save();
    });
    entryLogin($this, ['identifier' => $user->email, 'password' => 'a-long-test-password'])->assertOk();
    [$secret, $codes] = credentialEnroll($this, $user);
    config()->set('database.connections.auth_entry_competitor', config('database.connections.pgsql'));
    $other = DB::connection('auth_entry_competitor');
    $other->beginTransaction();
    try {
        $other->table('runa_auth.users')->where('id', $user->id)->lockForUpdate()->first();
        DB::statement("SET lock_timeout = '100ms'");
        $request = credentialRequest('/', ['method' => 'recovery', 'code' => $codes[0]]);
        expect(fn () => app(CredentialFactors::class)->handle($request, 'challenge'))->toThrow(QueryException::class, 'lock timeout');
        expect(User::find($user->id)->recovery_hashes)->toContain(hash('sha256', $codes[0]));
    } finally {
        DB::statement('RESET lock_timeout');
        $other->rollBack();
    }
});

it('keeps privileged factor recovery denied without authority and requires fresh both factor step up', function (): void {
    entryPolicy(['two_factor_required' => 'totp']);
    $actor = entryUser('recovery-actor@example.test');
    $target = entryUser('recovery-target@example.test');
    DB::transaction(function () use ($actor, $target): void {
        $actor->email_verified_at = now();
        $actor->save();
        $target->email_verified_at = now();
        $target->totp_secret = Crypt::encryptString(app(Google2FA::class)->generateSecretKey(32));
        $target->save();
    });
    entryLogin($this, ['identifier' => $target->email, 'password' => 'a-long-test-password'])->assertOk();
    entryLogin($this, ['identifier' => $actor->email, 'password' => 'a-long-test-password'])->assertOk();
    [$secret, $codes] = credentialEnroll($this, $actor);
    $api = app(ModuleApis::class)->resolve('runa.auth', AuthSecurity::class, '1.0.0');
    expect(fn () => $api->resetFactors($target->public_id, 1))->toThrow(OperationRejected::class);
    $scope = app(PrincipalScope::class);
    $reset = fn () => $scope->run(new TrustedPrincipal($actor->public_id), fn () => app(ManageSecurity::class)->resetFactors($target->public_id, 1));
    app()->instance('request', credentialRequest('/'));
    expect(fn () => $reset())->toThrow(OperationRejected::class);
    $this->postJson('/api/runa/auth/step-up', ['action' => 'runa.auth.two_factor.reset', 'requirement' => 'both',
        'current_password' => 'a-long-test-password', 'method' => 'recovery', 'code' => $codes[0]])->assertOk();
    app()->instance('request', credentialRequest('/'));
    expect($reset())->toBeTrue();
    expect(User::find($target->id)->totp_secret)->toBeNull()
        ->and(DB::table('runa_auth.sessions')->where('user_id', $target->id)->whereNull('revoked_at')->count())->toBe(0);
    entryLogin($this, ['identifier' => $target->email, 'password' => 'a-long-test-password'])->assertOk()
        ->assertJsonPath('data.restrictions', ['two_factor_enrollment_required']);
});

it('delivers reset and email OTP once through private mail and suppresses expired session delivery', function (): void {
    CarbonImmutable::setTestNow('2026-10-23 12:00:00 UTC');
    entryPolicy(['email_otp_enabled' => true, 'session_idle_minutes' => 1]);
    $user = entryUser('security-mail@example.test');
    DB::transaction(function () use ($user): void {
        $user->email_verified_at = now();
        $user->save();
    });
    entryLogin($this, ['identifier' => $user->email, 'password' => 'a-long-test-password'])->assertOk();
    $this->postJson('/api/runa/auth/security/email-enable', ['current_password' => 'a-long-test-password'])->assertOk();
    $this->postJson('/api/runa/auth/security/email-send')->assertOk();
    $token = DB::table('runa_auth.entry_tokens')->where('purpose', 'email_otp')->latest('id')->first();
    $code = Crypt::decryptString($token->delivery_secret);
    $delivery = app(DeliverEntryMail::class);
    $delivery->deliver($token->public_id);
    $delivery->deliver($token->public_id);
    $messages = app('mailer')->getSymfonyTransport()->messages();
    expect($messages)->toHaveCount(1)->and($messages[0]->getOriginalMessage()->getTextBody())->toContain($code);
    $this->postJson('/api/runa/auth/security/email-send')->assertOk();
    $pending = DB::table('runa_auth.entry_tokens')->where('purpose', 'email_otp')->latest('id')->first();
    CarbonImmutable::setTestNow('2026-10-23 12:01:00 UTC');
    $delivery->deliver($pending->public_id);
    expect(app('mailer')->getSymfonyTransport()->messages())->toHaveCount(1);
    app(Passwords::class)->requestReset($user->email);
    $reset = DB::table('runa_auth.entry_tokens')->where('purpose', 'reset')->first();
    $delivery->deliver($reset->public_id);
    $delivery->deliver($reset->public_id);
    $messages = app('mailer')->getSymfonyTransport()->messages();
    expect($messages)->toHaveCount(2)->and($messages[1]->getOriginalMessage()->getTextBody())->toContain('/auth/reset-password#token=');
});

it('rejects an email step up proof after policy begins requiring TOTP even with a valid TOTP session', function (): void {
    entryPolicy(['email_otp_enabled' => true]);
    $user = entryUser('proof-policy@example.test');
    DB::transaction(function () use ($user): void {
        $user->email_verified_at = now();
        $user->save();
    });
    entryLogin($this, ['identifier' => $user->email, 'password' => 'a-long-test-password'])->assertOk();
    [$secret, $codes] = credentialEnroll($this, $user);
    $this->postJson('/api/runa/auth/security/email-enable', ['current_password' => 'a-long-test-password', 'method' => 'recovery', 'code' => $codes[0]])->assertOk();
    $this->postJson('/api/runa/auth/security/challenge', ['method' => 'recovery', 'code' => $codes[1]])->assertOk();
    $this->postJson('/api/runa/auth/security/email-send')->assertOk();
    $token = DB::table('runa_auth.entry_tokens')->where('purpose', 'email_otp')->latest('id')->first();
    $this->postJson('/api/runa/auth/step-up', ['action' => 'app.invoice.publish', 'requirement' => 'both',
        'current_password' => 'a-long-test-password', 'method' => 'email', 'code' => Crypt::decryptString($token->delivery_secret)])->assertOk();
    entryPolicy(['two_factor_required' => 'totp']);
    $this->getJson('/api/runa/auth/session')->assertOk()->assertJsonPath('data.restrictions', []);
    app()->instance('request', credentialRequest('/'));
    $scope = app(PrincipalScope::class);
    expect(fn () => DB::transaction(fn () => $scope->run(new TrustedPrincipal($user->public_id),
        fn () => app(StepUp::class)->assertVerified('app.invoice.publish', 'both'))))->toThrow(OperationRejected::class);
});

function profileVerified(string $email): User
{
    $user = entryUser($email);
    DB::transaction(function () use ($user): void {
        $user->email_verified_at = now();
        $user->save();
    });

    return $user;
}

function profileSecret(string $purpose): string
{
    return Crypt::decryptString(DB::table('runa_auth.profile_mail')->where('purpose', $purpose)->latest('id')->value('delivery_secret'));
}

it('keeps private profile edits versioned and self scoped with complete naming policy and safe history', function (): void {
    $user = profileVerified('private-profile@example.test');
    $other = profileVerified('other-profile@example.test');
    entryLogin($this, ['identifier' => $user->email, 'password' => 'a-long-test-password'])->assertOk();
    $this->getJson('/api/runa/auth/profile?public_id='.$other->public_id)->assertOk()
        ->assertJsonPath('data.public_id', $user->public_id)->assertJsonPath('data.initials', 'TU');
    $this->patchJson('/api/runa/auth/profile', ['version' => 1, 'first_name' => 'Anna', 'surname' => 'Żółć', 'login' => ' anna '])
        ->assertOk()->assertJsonPath('data.display_name', 'Anna Żółć')->assertJsonPath('data.initials', 'AŻ')->assertJsonPath('data.login', 'anna');
    $this->patchJson('/api/runa/auth/profile', ['version' => 1, 'name' => 'Stale'])->assertConflict()->assertJsonPath('error.code', 'identity_stale');
    $this->patchJson('/api/runa/auth/profile', ['version' => 2, 'name' => 'Mixed', 'surname' => 'Invalid'])->assertUnprocessable();
    entryPolicy(['own_name_editable' => false, 'own_account_history' => true, 'own_login_history' => true]);
    $this->patchJson('/api/runa/auth/profile', ['version' => 2, 'name' => 'Read only'])->assertForbidden();
    DB::transaction(fn () => app(EntryTransaction::class)->record('profile.changed', $other));
    $history = $this->getJson('/api/runa/auth/profile/history?kind=account')->assertOk()->json('data');
    expect($history['entries'])->toHaveCount(1)->and(array_keys($history['entries'][0]))->toBe(['public_id', 'occurred_at', 'action', 'outcome']);
    $foreign = DB::table('runa_audit.events')->latest('id')->value('public_id');
    $this->getJson('/api/runa/auth/profile/history?kind=account&before='.$foreign)->assertUnprocessable();
    $login = $this->getJson('/api/runa/auth/profile/history?kind=login')->assertOk()->json('data.entries');
    expect(array_column($login, 'action'))->toContain('login.succeeded');
    expect(json_encode($history))->not->toContain($other->public_id, 'private-profile@example.test', 'actor', 'execution', 'changes');
    entryPolicy(['own_account_history' => false]);
    $this->getJson('/api/runa/auth/profile/history?kind=account')->assertForbidden();
});

it('preserves the old primary until both email confirmations and atomically revokes other sessions', function (): void {
    entryPolicy(['email_change_old_confirmation' => true]);
    $user = profileVerified('old-primary@example.test');
    entryLogin($this, ['identifier' => $user->email, 'password' => 'a-long-test-password'])->assertOk();
    $firstSession = DB::table('runa_auth.sessions')->latest('id')->value('id');
    entryLogin($this, ['identifier' => $user->email, 'password' => 'a-long-test-password'])->assertOk();
    $this->postJson('/api/runa/auth/profile/email', ['version' => 1, 'email' => ' New-Primary@Example.test ', 'current_password' => 'wrong'])->assertUnprocessable();
    $this->postJson('/api/runa/auth/profile/email', ['version' => 1, 'email' => ' New-Primary@Example.test ', 'current_password' => 'a-long-test-password'])->assertOk();
    $new = profileSecret('primary_new');
    $old = profileSecret('primary_old');
    expect(User::find($user->id)->email)->toBe('old-primary@example.test');
    $this->postJson('/api/runa/auth/email/complete', ['token' => $new])->assertOk();
    expect(User::find($user->id)->email)->toBe('old-primary@example.test');
    $this->postJson('/api/runa/auth/email/complete', ['token' => $new])->assertUnprocessable();
    $this->postJson('/api/runa/auth/email/complete', ['token' => $old])->assertOk();
    expect(User::find($user->id)->email)->toBe('new-primary@example.test')
        ->and(DB::table('runa_auth.sessions')->where('id', $firstSession)->value('revoked_at'))->not->toBeNull()
        ->and(DB::table('runa_auth.sessions')->where('user_id', $user->id)->whereNull('revoked_at')->count())->toBe(1)
        ->and(DB::table('runa_auth.profile_mail')->where('purpose', 'notice')->value('recipient'))->toBe('old-primary@example.test');
    $this->getJson('/api/runa/auth/profile')->assertOk()->assertJsonPath('data.email', 'new-primary@example.test');
    expect(DB::table('runa_audit.events')->get()->toJson())->not->toContain($new, $old, 'new-primary@example.test', 'old-primary@example.test');
});

it('arbitrates email completion conflicts and rolls back confirmations and notice when Audit fails', function (): void {
    $user = profileVerified('email-atomic@example.test');
    entryLogin($this, ['identifier' => $user->email, 'password' => 'a-long-test-password'])->assertOk();
    $this->postJson('/api/runa/auth/profile/email', ['version' => 1, 'email' => 'email-claimed@example.test', 'current_password' => 'a-long-test-password'])->assertOk();
    $secret = profileSecret('primary_new');
    $other = entryUser('email-claimed@example.test');
    $this->postJson('/api/runa/auth/email/complete', ['token' => $secret])->assertConflict();
    expect(User::find($user->id)->email)->toBe($user->email)->and(DB::table('runa_auth.profile_mail')->where('purpose', 'primary_new')->value('consumed_at'))->toBeNull();
    // Rotate the request; the prior address token can no longer be redeemed.
    $this->postJson('/api/runa/auth/profile/email', ['version' => 2, 'email' => 'email-free@example.test', 'current_password' => 'a-long-test-password'])->assertOk();
    $this->postJson('/api/runa/auth/email/complete', ['token' => $secret])->assertUnprocessable();
    $secret = profileSecret('primary_new');
    DB::statement('ALTER TABLE runa_audit.events ADD CONSTRAINT profile_fixture_refusal CHECK (false) NOT VALID');
    $this->postJson('/api/runa/auth/email/complete', ['token' => $secret])->assertServiceUnavailable();
    expect(User::find($user->id)->email)->toBe($user->email)->and(DB::table('runa_auth.profile_mail')->where('purpose', 'notice')->count())->toBe(0);
    DB::statement('ALTER TABLE runa_audit.events DROP CONSTRAINT profile_fixture_refusal');
    $this->postJson('/api/runa/auth/email/complete', ['token' => $secret])->assertOk();
});

it('keeps secondary addresses independently verified notification only and stops routing on removal', function (): void {
    entryPolicy(['secondary_emails_enabled' => true]);
    $user = profileVerified('secondary-owner@example.test');
    $other = profileVerified('secondary-other@example.test');
    entryLogin($this, ['identifier' => $user->email, 'password' => 'a-long-test-password'])->assertOk();
    $add = $this->postJson('/api/runa/auth/profile/secondary-emails', ['version' => 1, 'email' => 'notice-only@example.test'])->assertOk()->json('data.public_id');
    $manager = app(ManageProfiles::class);
    expect($manager->notificationEmails($user->public_id))->toBe([]);
    $secret = profileSecret('secondary');
    $this->postJson('/api/runa/auth/email/complete', ['token' => $secret])->assertOk();
    expect($manager->notificationEmails($user->public_id))->toBe(['notice-only@example.test']);
    expect(app(LookupIdentity::class)->forIdentifier('notice-only@example.test'))->toBeNull();
    expect(fn () => app(ChangeIdentity::class)->create(new NewIdentity('notice-only@example.test', name: 'Collision')))->toThrow(IdentityConflict::class);
    expect(fn () => $manager->addSecondary($other->public_id, 1, 'notice-only@example.test'))->toThrow(IdentityConflict::class);
    entryPolicy(['secondary_email_sharing' => true]);
    $otherAddress = $manager->addSecondary($other->public_id, 1, 'notice-only@example.test');
    expect($manager->notificationEmails($other->public_id))->toBe([]);
    $this->postJson('/api/runa/auth/email/complete', ['token' => profileSecret('secondary')])->assertOk();
    expect($manager->notificationEmails($other->public_id))->toBe(['notice-only@example.test']);
    $this->deleteJson('/api/runa/auth/profile/secondary-emails', ['version' => 3, 'address_id' => $otherAddress])->assertUnprocessable();
    $this->deleteJson('/api/runa/auth/profile/secondary-emails', ['version' => 3, 'address_id' => $add])->assertOk();
    expect($manager->notificationEmails($user->public_id))->toBe([])->and($manager->notificationEmails($other->public_id))->toBe(['notice-only@example.test']);
    $this->postJson('/api/runa/auth/email/complete', ['token' => $secret])->assertUnprocessable();
});

it('requires dedicated permission fresh both factor proof and configured bypass for administrative email changes', function (): void {
    $actor = profileVerified('email-admin@example.test');
    $target = profileVerified('email-target@example.test');
    entryLogin($this, ['identifier' => $target->email, 'password' => 'a-long-test-password'])->assertOk();
    entryLogin($this, ['identifier' => $actor->email, 'password' => 'a-long-test-password'])->assertOk();
    [$secret, $codes] = credentialEnroll($this, $actor);
    $api = app(ModuleApis::class)->resolve('runa.auth', AuthProfiles::class, '1.0.0');
    expect(fn () => $api->replaceVerifiedEmail($target->public_id, 1, 'bypass@example.test'))->toThrow(OperationRejected::class);
    app()->instance('request', credentialRequest('/'));
    $bypass = fn () => app(PrincipalScope::class)->run(new TrustedPrincipal($actor->public_id), fn () => app(ManageProfiles::class)->replaceVerifiedEmail($target->public_id, 1, 'bypass@example.test'));
    expect($bypass)->toThrow(OperationRejected::class);
    $this->postJson('/api/runa/auth/step-up', ['action' => 'runa.auth.email.admin_bypass', 'requirement' => 'both', 'current_password' => 'a-long-test-password', 'method' => 'recovery', 'code' => $codes[0]])->assertOk();
    app()->instance('request', credentialRequest('/'));
    expect($bypass)->toThrow(OperationRejected::class);
    entryPolicy(['admin_email_verification_bypass' => true]);
    expect($bypass())->toBeTrue();
    expect(User::find($target->id)->email)->toBe('bypass@example.test')->and(User::find($target->id)->email_verified_at)->not->toBeNull()
        ->and(DB::table('runa_auth.sessions')->where('user_id', $target->id)->whereNull('revoked_at')->count())->toBe(0)
        ->and(DB::table('runa_auth.profile_mail')->where('purpose', 'notice')->value('recipient'))->toBe($target->email);
    expect($bypass)->toThrow(OperationRejected::class);
});

it('forces immediate reverification on an existing session and restores only current eligible access', function (): void {
    $actor = profileVerified('reverify-admin@example.test');
    $target = profileVerified('reverify-target@example.test');
    entryLogin($this, ['identifier' => $target->email, 'password' => 'a-long-test-password'])->assertOk();
    $targetSession = app('session')->getId();
    $targetStore = clone app('session')->driver();
    entryLogin($this, ['identifier' => $actor->email, 'password' => 'a-long-test-password'])->assertOk();
    [$secret, $codes] = credentialEnroll($this, $actor);
    $this->postJson('/api/runa/auth/step-up', ['action' => 'runa.auth.email.require_verification', 'requirement' => 'both', 'current_password' => 'a-long-test-password', 'method' => 'recovery', 'code' => $codes[0]])->assertOk();
    app()->instance('request', credentialRequest('/'));
    app(PrincipalScope::class)->run(new TrustedPrincipal($actor->public_id), fn () => app(ManageProfiles::class)->requireVerification($target->public_id, 1));
    $token = DB::table('runa_auth.entry_tokens')->where('user_id', $target->id)->where('purpose', 'verification')->latest('id')->first();
    Auth::forgetGuards();
    // The test transport reused one browser for two logins; restore the other browser's native session record.
    $targetStore->save();
    $this->withCredentials()->withCookie('runa_session', $targetSession);
    $this->getJson('/api/runa/auth/session')->assertOk()->assertJsonPath('data.restrictions', ['email_verification_required']);
    $this->getJson('/api/runa/auth/profile')->assertForbidden();
    $this->postJson('/api/runa/auth/verification/complete', ['token' => Crypt::decryptString($token->delivery_secret)])->assertOk();
    $this->getJson('/api/runa/auth/profile')->assertOk();
});

it('preserves disabled avatars and safely cleans replaced private blobs after Audit commit', function (): void {
    $this->profileAvatarRoot = sys_get_temp_dir().'/runa-profile-avatar-'.bin2hex(random_bytes(8));
    config()->set('filesystems.disks.runa_private', ['driver' => 'local', 'root' => $this->profileAvatarRoot, 'throw' => true]);
    Storage::forgetDisk('runa_private');
    $user = profileVerified('avatar-owner@example.test');
    entryLogin($this, ['identifier' => $user->email, 'password' => 'a-long-test-password'])->assertOk();
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jpuQAAAAASUVORK5CYII=');
    $file = fn () => UploadedFile::fake()->createWithContent('avatar.png', $png);
    $this->post('/api/runa/auth/profile/avatar', ['version' => 1, 'avatar' => $file()], ['Accept' => 'application/json'])->assertOk();
    $path = User::find($user->id)->avatar_path;
    $disk = app(ModuleStorage::class)->disk('runa.auth');
    $this->get('/api/runa/auth/profile/avatar')->assertOk()->assertHeader('Content-Type', 'image/png')->assertHeader('X-Content-Type-Options', 'nosniff');
    entryPolicy(['avatar_enabled' => false]);
    $this->get('/api/runa/auth/profile/avatar')->assertNotFound();
    $this->getJson('/api/runa/auth/profile')->assertOk()->assertJsonPath('data.has_avatar', false);
    expect($disk->exists($path))->toBeTrue();
    entryPolicy(['avatar_enabled' => true]);
    DB::statement('ALTER TABLE runa_audit.events ADD CONSTRAINT avatar_fixture_refusal CHECK (false) NOT VALID');
    $this->post('/api/runa/auth/profile/avatar', ['version' => 2, 'avatar' => $file()], ['Accept' => 'application/json'])->assertServiceUnavailable();
    expect(User::find($user->id)->avatar_path)->toBe($path)->and($disk->exists($path))->toBeTrue();
    DB::statement('ALTER TABLE runa_audit.events DROP CONSTRAINT avatar_fixture_refusal');
    $this->post('/api/runa/auth/profile/avatar', ['version' => 2, 'avatar' => $file()], ['Accept' => 'application/json'])->assertOk();
    expect($disk->exists($path))->toBeFalse();
    $unsafe = UploadedFile::fake()->createWithContent('unsafe.png', '<svg><script>bad</script></svg>');
    $this->post('/api/runa/auth/profile/avatar', ['version' => 3, 'avatar' => $unsafe], ['Accept' => 'application/json'])->assertUnprocessable();
    entryPolicy(['avatar_editor' => 'neither']);
    $this->deleteJson('/api/runa/auth/profile/avatar', ['version' => 3])->assertForbidden();
    CarbonImmutable::setTestNow(CarbonImmutable::now('UTC')->addMinutes(31));
    app(Avatars::class)->cleanup();
    expect($disk->files('avatars/'.$user->public_id))->toHaveCount(1);
    $this->postJson('/api/runa/auth/logout')->assertOk();
    Auth::forgetGuards();
    $this->get('/api/runa/auth/profile/avatar', ['Accept' => 'application/json'])->assertUnauthorized();
});

it('keeps self deactivation disabled by default and consumes step up atomically with authority and Audit', function (): void {
    $user = profileVerified('self-lifecycle@example.test');
    entryLogin($this, ['identifier' => $user->email, 'password' => 'a-long-test-password'])->assertOk();
    $this->postJson('/api/runa/auth/profile/lifecycle', ['version' => 1, 'operation' => 'deactivate'])->assertForbidden();
    entryPolicy(['self_deactivate' => true]);
    $this->postJson('/api/runa/auth/profile/lifecycle', ['version' => 1, 'operation' => 'deactivate'])->assertForbidden();
    $this->postJson('/api/runa/auth/step-up', ['action' => 'runa.auth.self.deactivate', 'requirement' => 'password', 'current_password' => 'a-long-test-password'])->assertOk();
    DB::statement('ALTER TABLE runa_audit.events ADD CONSTRAINT self_fixture_refusal CHECK (false) NOT VALID');
    $this->postJson('/api/runa/auth/profile/lifecycle', ['version' => 1, 'operation' => 'deactivate'])->assertServiceUnavailable();
    expect(User::find($user->id)->active)->toBeTrue()->and(DB::table('runa_auth.step_up_proofs')->value('consumed_at'))->toBeNull();
    DB::statement('ALTER TABLE runa_audit.events DROP CONSTRAINT self_fixture_refusal');
    $this->postJson('/api/runa/auth/profile/lifecycle', ['version' => 1, 'operation' => 'deactivate'])->assertOk();
    $this->getJson('/api/runa/auth/profile')->assertUnauthorized();
    expect(User::find($user->id)->active)->toBeFalse();
});

it('delivers private address links once and sends the completion notice only to the former primary', function (): void {
    $user = profileVerified('profile-mail-old@example.test');
    entryLogin($this, ['identifier' => $user->email, 'password' => 'a-long-test-password'])->assertOk();
    $this->postJson('/api/runa/auth/profile/email', ['version' => 1, 'email' => 'profile-mail-new@example.test', 'current_password' => 'a-long-test-password'])->assertOk();
    $mail = DB::table('runa_auth.profile_mail')->where('purpose', 'primary_new')->first();
    $secret = profileSecret('primary_new');
    expect(DB::table('shared.jobs')->get()->toJson())->not->toContain($secret, 'profile-mail-new@example.test');
    $delivery = app(ProfileMail::class);
    $delivery->deliver($mail->public_id);
    $delivery->deliver($mail->public_id);
    $messages = app('mailer')->getSymfonyTransport()->messages();
    expect($messages)->toHaveCount(1)->and($messages[0]->getOriginalMessage()->getTo()[0]->getAddress())->toBe('profile-mail-new@example.test')
        ->and($messages[0]->getOriginalMessage()->getTextBody())->toContain('/auth/confirm-address#token='.$secret);
    $this->postJson('/api/runa/auth/email/complete', ['token' => $secret])->assertOk();
    $notice = DB::table('runa_auth.profile_mail')->where('purpose', 'notice')->first();
    $this->postJson('/api/runa/auth/profile/email', ['version' => 3, 'email' => 'profile-mail-stale@example.test', 'current_password' => 'a-long-test-password'])->assertOk();
    $stale = DB::table('runa_auth.profile_mail')->where('purpose', 'primary_new')->latest('id')->first();
    app(ChangeIdentity::class)->change($user->public_id, 4, new LifecycleChange('deactivate'));
    $delivery->deliver($notice->public_id);
    $delivery->deliver($notice->public_id);
    $messages = app('mailer')->getSymfonyTransport()->messages();
    expect($messages)->toHaveCount(2)->and($messages[1]->getOriginalMessage()->getTo()[0]->getAddress())->toBe('profile-mail-old@example.test');
    $delivery->deliver($stale->public_id);
    expect(app('mailer')->getSymfonyTransport()->messages())->toHaveCount(2)
        ->and(DB::table('runa_auth.profile_mail')->where('id', $stale->id)->value('delivery_secret'))->toBeNull();
});

it('expires address tokens exactly at their deadline and invalidates pending changes on password replacement', function (): void {
    CarbonImmutable::setTestNow('2026-10-23 12:00:00 UTC');
    entryPolicy(['email_change_minutes' => 5]);
    $user = profileVerified('profile-expiry@example.test');
    entryLogin($this, ['identifier' => $user->email, 'password' => 'a-long-test-password'])->assertOk();
    $this->postJson('/api/runa/auth/profile/email', ['version' => 1, 'email' => 'profile-expired@example.test', 'current_password' => 'a-long-test-password'])->assertOk();
    $expired = profileSecret('primary_new');
    CarbonImmutable::setTestNow('2026-10-23 12:05:00 UTC');
    $this->postJson('/api/runa/auth/email/complete', ['token' => $expired])->assertUnprocessable();
    $this->postJson('/api/runa/auth/profile/email', ['version' => 2, 'email' => 'profile-password@example.test', 'current_password' => 'a-long-test-password'])->assertOk();
    $secret = profileSecret('primary_new');
    $this->postJson('/api/runa/auth/password/change', ['current_password' => 'a-long-test-password', 'password' => 'different-long-test-password', 'password_confirmation' => 'different-long-test-password'])->assertOk();
    $this->postJson('/api/runa/auth/email/complete', ['token' => $secret])->assertUnprocessable();
    expect(User::find($user->id)->email)->toBe('profile-expiry@example.test');
});

it('applies verification new account and all account policy independently from forced reverification', function (): void {
    entryPolicy(['email_verification' => 'disabled']);
    $old = entryUser('optional-verification@example.test');
    entryLogin($this, ['identifier' => $old->email, 'password' => 'a-long-test-password'])->assertOk()->assertJsonPath('data.restrictions', []);
    entryPolicy(['email_verification' => 'new']);
    $this->getJson('/api/runa/auth/profile')->assertOk();
    $new = entryUser('required-verification@example.test');
    expect(app(LookupIdentity::class)->eligibility($new->public_id)->restrictions)->toContain('email_verification_required');
    entryPolicy(['email_verification' => 'all']);
    $this->getJson('/api/runa/auth/profile')->assertForbidden();
    $this->getJson('/api/runa/auth/session')->assertOk()->assertJsonPath('data.restrictions', ['email_verification_required']);
});

it('honors each captured primary email session policy without reviving trust', function (string $policy, int $retained): void {
    entryPolicy(['email_change_session_policy' => $policy]);
    $user = profileVerified('email-session-policy@example.test');
    entryLogin($this, ['identifier' => $user->email, 'password' => 'a-long-test-password'])->assertOk();
    entryLogin($this, ['identifier' => $user->email, 'password' => 'a-long-test-password'])->assertOk();
    $this->postJson('/api/runa/auth/profile/email', ['version' => 1, 'email' => 'email-session-changed@example.test', 'current_password' => 'a-long-test-password'])->assertOk();
    $this->postJson('/api/runa/auth/email/complete', ['token' => profileSecret('primary_new')])->assertOk();
    expect(DB::table('runa_auth.sessions')->where('user_id', $user->id)->whereNull('revoked_at')->count())->toBe($retained);
})->with([['keep_all', 2], ['revoke_others', 1], ['revoke_all', 0]]);

it('serializes primary confirmation against another worker holding the account row', function (): void {
    $user = profileVerified('address-row-lock@example.test');
    entryLogin($this, ['identifier' => $user->email, 'password' => 'a-long-test-password'])->assertOk();
    $this->postJson('/api/runa/auth/profile/email', ['version' => 1, 'email' => 'address-row-new@example.test', 'current_password' => 'a-long-test-password'])->assertOk();
    $secret = profileSecret('primary_new');
    config()->set('database.connections.auth_entry_competitor', config('database.connections.pgsql'));
    $other = DB::connection('auth_entry_competitor');
    $other->beginTransaction();
    try {
        $other->table('runa_auth.users')->where('id', $user->id)->lockForUpdate()->first();
        DB::statement("SET lock_timeout = '100ms'");
        expect(fn () => app(EmailAddresses::class)->complete($secret))->toThrow(QueryException::class, 'lock timeout');
        expect(User::find($user->id)->email)->toBe($user->email);
    } finally {
        DB::statement('RESET lock_timeout');
        $other->rollBack();
    }
    app(EmailAddresses::class)->complete($secret);
    expect(fn () => app(EmailAddresses::class)->complete($secret))->toThrow(OperationRejected::class);
});

it('rechecks profile restrictions under the account lock after admission has already occurred', function (): void {
    $user = profileVerified('profile-late-restriction@example.test');
    entryLogin($this, ['identifier' => $user->email, 'password' => 'a-long-test-password'])->assertOk();
    DB::transaction(function () use ($user): void {
        $fresh = User::find($user->id);
        $fresh->email_verified_at = null;
        $fresh->verification_forced = true;
        $fresh->save();
    });
    expect(fn () => app(Profiles::class)->edit(credentialRequest('/', ['version' => 1, 'name' => 'Forbidden after admission'])))
        ->toThrow(OperationRejected::class);
    expect(User::find($user->id)->name)->toBe('Test User');
});

it('persists declared own preferences with revision conflicts adoption reset and account isolation', function (): void {
    entryPolicy(['email_verification' => 'disabled']);
    $user = entryUser('preferences@example.test');
    entryLogin($this, ['identifier' => $user->email, 'password' => 'a-long-test-password'])->assertOk();
    $endpoint = '/api/runa/auth/preferences';
    $base = ['application' => 'runa'];
    $scope = ['module' => 'runa.auth', 'view' => 'global', 'key' => 'locale'];
    $this->postJson($endpoint, $base + ['operation' => 'snapshot'])->assertOk()->assertJsonPath('data.revision', 0);
    config()->set('branding.application_id', 'custom-app');
    $custom = $this->postJson($endpoint, ['application' => 'custom-app', 'operation' => 'snapshot'])->assertOk()->json('data.values');
    expect(array_column($custom, 'scope'))->toContain('custom-app:runa.auth:navigation:expanded');
    $this->postJson($endpoint, $base + $scope + ['operation' => 'write', 'value' => 'en', 'revision' => 0])
        ->assertOk()->assertJsonPath('data.value', 'en')->assertJsonPath('data.revision', 1);
    $this->postJson($endpoint, $base + $scope + ['operation' => 'write', 'value' => 'pl', 'revision' => 0])->assertStatus(409);
    $this->postJson($endpoint, $base + $scope + ['operation' => 'write', 'value' => 'invalid', 'revision' => 1])->assertStatus(422);
    $this->postJson($endpoint, $base + array_replace($scope, ['key' => 'undeclared']) + ['operation' => 'write', 'value' => 'en', 'revision' => 1])->assertStatus(422);
    // Explicit guest adoption is allowlisted; server values win.
    $this->postJson($endpoint, $base + ['operation' => 'adopt', 'revision' => 1, 'values' => [
        $scope + ['value' => 'pl'], ['module' => 'runa.auth', 'view' => 'global', 'key' => 'theme', 'value' => 'dark'],
    ]])->assertOk()->assertJsonPath('data.legacy_claimed', true)->assertJsonPath('data.revision', 2);
    $this->postJson($endpoint, $base + ['operation' => 'adopt', 'revision' => 2, 'values' => []])->assertStatus(409);
    $this->postJson($endpoint, $base + $scope + ['operation' => 'reset', 'revision' => 2])->assertOk()->assertJsonPath('data.revision', 3);
    $this->postJson($endpoint, $base + ['operation' => 'snapshot'])->assertOk()->assertJsonPath('data.values.0.value', 'pl');
    // A fresh login/device reads PostgreSQL, and another account has its own defaults.
    $this->postJson('/api/runa/auth/logout')->assertOk();
    entryLogin($this, ['identifier' => $user->email, 'password' => 'a-long-test-password'])->assertOk();
    $this->postJson($endpoint, $base + ['operation' => 'snapshot'])->assertOk()->assertJsonPath('data.revision', 3);
    $this->postJson('/api/runa/auth/logout')->assertOk();
    $other = entryUser('other-preferences@example.test');
    entryLogin($this, ['identifier' => $other->email, 'password' => 'a-long-test-password'])->assertOk();
    $this->postJson($endpoint, $base + ['operation' => 'snapshot'])->assertOk()->assertJsonPath('data.revision', 0)->assertJsonPath('data.values.1.value', 'system');
    app(PrincipalScope::class)->run(new TrustedPrincipal($other->public_id), function (): void {
        $api = app(ModuleApis::class)->resolve('runa.auth', AuthPreferences::class, '1.0.0');
        $scope = new PreferenceScope('runa', 'runa.auth', 'global', 'theme');
        expect($api->read($scope)['value'])->toBe('system');
        expect($api->write($scope, new PreferenceValue('dark'), 0)['value'])->toBe('dark');
        expect(fn () => $api->resetAccount((string) Str::uuid7(), 0))->toThrow(OperationRejected::class);
    });
});

it('protects mandatory routing and revalidates obsolete reminders with single SMTP attempts', function (): void {
    entryPolicy(['email_verification' => 'disabled', 'notice_account_expiry' => true, 'notice_interval_minutes' => 1]);
    $user = entryUser('notices@example.test');
    DB::transaction(function () use ($user): void {
        $user->email_verified_at = now();
        $user->expires_at = now()->addDays(2);
        $user->save();
    });
    entryLogin($this, ['identifier' => $user->email, 'password' => 'a-long-test-password'])->assertOk();
    $this->postJson('/api/runa/auth/delivery-preferences', ['operation' => 'write', 'module' => 'runa.auth', 'category' => 'locked',
        'revision' => 0, 'enabled' => false, 'recipients' => ['primary']])->assertStatus(422);
    $this->postJson('/api/runa/auth/delivery-preferences', ['operation' => 'write', 'module' => 'runa.auth', 'category' => 'new_browser',
        'revision' => 0, 'enabled' => true, 'recipients' => [(string) Str::uuid7()]])->assertStatus(422);
    $notices = app(SecurityNotices::class);
    $notices->reminders();
    $notices->reminders();
    expect(DB::table('runa_auth.security_notices')->where('category', 'account_expiry')->count())->toBe(1);
    $notice = DB::table('runa_auth.security_notices')->where('category', 'account_expiry')->first();
    $sent = 0;
    app('events')->listen(MessageSending::class, function () use (&$sent): void {
        $sent++;
    });
    // State changes between enqueue and execution make the reminder obsolete.
    DB::transaction(function () use ($user): void {
        $user->expires_at = now()->addDays(30);
        $user->save();
    });
    $notices->deliver($notice->public_id);
    expect($sent)->toBe(0);
    $this->postJson('/api/runa/auth/password/change', ['current_password' => 'a-long-test-password', 'password' => 'a-new-long-password', 'password_confirmation' => 'a-new-long-password'])
        ->assertOk();
    $passwordNotice = DB::table('runa_auth.security_notices')->where('category', 'password_changed')->first();
    expect($passwordNotice)->not->toBeNull();
    $notices->deliver($passwordNotice->public_id);
    $notices->deliver($passwordNotice->public_id);
    expect($sent)->toBe(1)->and(DB::table('runa_auth.security_notices')->where('id', $passwordNotice->id)->value('sent_at'))->not->toBeNull();
});

it('rolls back delivery preferences and their revision when mandatory Audit persistence fails', function (): void {
    entryPolicy(['email_verification' => 'disabled']);
    $user = entryUser('delivery-atomic@example.test');
    entryLogin($this, ['identifier' => $user->email, 'password' => 'a-long-test-password'])->assertOk();
    DB::statement('ALTER TABLE runa_audit.events ADD CONSTRAINT delivery_fixture_refusal CHECK (false) NOT VALID');
    $this->postJson('/api/runa/auth/delivery-preferences', ['operation' => 'write', 'module' => 'runa.auth', 'category' => 'new_browser',
        'revision' => 0, 'enabled' => false, 'recipients' => ['primary']])->assertServiceUnavailable();
    expect(User::find($user->id)->preference_revision)->toBe(0)
        ->and(DB::table('runa_auth.delivery_preferences')->where('user_id', $user->id)->count())->toBe(0);
    DB::statement('ALTER TABLE runa_audit.events DROP CONSTRAINT delivery_fixture_refusal');
});

it('keeps settings history effects and Audit atomic and restores as a new version', function (): void {
    $user = entryUser('policy-target@example.test');
    $settings = app(ModuleSettings::class);
    $state = $settings->read('runa.auth');
    $revision = $state['revision'];
    DB::statement('ALTER TABLE runa_auth.policy_versions ADD CONSTRAINT policy_fixture_refusal CHECK (false) NOT VALID');
    expect(fn () => $settings->update('runa.auth', ['password_min_length' => 14], $revision, $state['generation']))->toThrow(QueryException::class);
    expect($settings->read('runa.auth')['revision'])->toBe($revision)
        ->and(DB::table('shared.module_transitions')->whereIn('status', ModuleRegistry::ACTIVE_TRANSITIONS)->count())->toBe(0);
    DB::statement('ALTER TABLE runa_auth.policy_versions DROP CONSTRAINT policy_fixture_refusal');
    DB::statement('ALTER TABLE runa_audit.events ADD CONSTRAINT policy_audit_refusal CHECK (false) NOT VALID');
    expect(fn () => $settings->update('runa.auth', ['password_min_length' => 14], $revision, $state['generation']))->toThrow(AuditUnavailable::class);
    expect(DB::table('runa_auth.policy_versions')->count())->toBe(1);
    DB::statement('ALTER TABLE runa_audit.events DROP CONSTRAINT policy_audit_refusal');
    $preview = app(Policies::class)->preview(['password_min_length' => 14, 'password_policy_effect' => 'force_change'], $revision, $state['generation']);
    expect($preview['consequences']['password_accounts'])->toBe(1);
    entryPolicy(['password_min_length' => 14, 'password_policy_effect' => 'force_change']);
    expect(User::find($user->id)->password_change_required)->toBeTrue();
    app(OperatorAccounts::class)->restorePolicy($revision);
    expect($settings->value('runa.auth', 'password_min_length'))->toBe(12)
        ->and($settings->read('runa.auth')['revision'])->toBe($revision + 2)
        ->and(User::find($user->id)->password_change_required)->toBeTrue()
        ->and(DB::table('runa_auth.policy_versions')->count())->toBe(3);
    expect(fn () => app(ModuleApis::class)->resolve('runa.auth', AuthPolicies::class, '1.0.0')->history())->toThrow(OperationRejected::class);
})->group('auth-policy-operators');

function operatorAuthorityFixture(): object
{
    $adapter = new class implements OperatorAuthority
    {
        public array $ids = [];

        public function initialize(Connection $connection, string $publicId): void
        {
            expect($connection)->toBe(DB::connection())->and($connection->transactionLevel())->toBeGreaterThan(0);
            $this->ids[] = $publicId;
        }

        public function isAdministrator(Connection $connection, string $publicId): bool
        {
            return in_array($publicId, $this->ids, true);
        }
    };
    app()->instance(OperatorAuthority::class, $adapter);

    return $adapter;
}

it('initializes once and redeems operator recovery without bypassing mandatory TOTP', function (): void {
    $accounts = app(OperatorAccounts::class);
    $input = new NewIdentity('first-admin@example.test', name: 'First Admin');
    expect(fn () => $accounts->initialize($input))->toThrow(RuntimeException::class)
        ->and(User::count())->toBe(0);
    $authority = operatorAuthorityFixture();
    $material = $accounts->initialize($input);
    $rotated = $accounts->initialize($input);
    expect($authority->ids)->toHaveCount(1)->and(User::count())->toBe(1);
    expect(fn () => $accounts->initialize(new NewIdentity('other-admin@example.test', name: 'Other')))->toThrow(RuntimeException::class);
    expect(fn () => app(Passwords::class)->reset($material['token'], 'new-administrator-password'))->toThrow(OperationRejected::class);
    app(Passwords::class)->reset($rotated['token'], 'new-administrator-password');
    expect(Auth::guard('web')->check())->toBeFalse();
    expect(fn () => app(Passwords::class)->reset($rotated['token'], 'another-administrator-password'))->toThrow(OperationRejected::class);
    $user = User::where('public_id', $material['public_id'])->first();
    DB::transaction(function () use ($user): void {
        $user->active = false;
        $user->email_verified_at = now();
        $user->save();
    });
    entryPolicy(['two_factor_required' => 'totp']);
    $recovery = $accounts->recover($user->public_id);
    entryLogin($this, ['identifier' => $user->email, 'password' => 'new-administrator-password'])->assertUnprocessable();
    app(Passwords::class)->reset($recovery['token'], 'recovered-administrator-password');
    entryLogin($this, ['identifier' => $user->email, 'password' => 'recovered-administrator-password'])
        ->assertOk()->assertJsonPath('data.restrictions', ['two_factor_enrollment_required']);
    expect(DB::table('runa_audit.events')->get()->toJson())->not->toContain($material['token'], $rotated['token'], $recovery['token'], 'new-administrator-password');
    config()->set('auth.operator_recovery_enabled', false);
    expect(fn () => $accounts->recover($user->public_id))->toThrow(RuntimeException::class);
})->group('auth-policy-operators');

it('operator command requires confirmation and privately hands off without stdout secrets', function (): void {
    $authority = operatorAuthorityFixture();
    $directory = sys_get_temp_dir().'/runa-operator-'.Str::uuid7();
    mkdir($directory, 0700);
    $file = $directory.'/setup.json';
    try {
        expect(Artisan::call('runa:auth:initialize', ['--email' => 'operator-cli@example.test', '--name' => 'Operator', '--output' => $file]))->toBe(1);
        expect(Artisan::output())->toContain('repeat with --confirm');
        expect(file_exists($file))->toBeFalse()->and(User::count())->toBe(0);
        expect(Artisan::call('runa:auth:initialize', ['--email' => 'operator-cli@example.test', '--name' => 'Operator', '--output' => $file, '--confirm' => true]))->toBe(0);
        expect(Artisan::output())->toContain('Private one-time handoff written');
        $material = json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
        expect(Artisan::output())->not->toContain($material['token']);
        expect(fileperms($file) & 0777)->toBe(0600)->and($material['token'])->toHaveLength(64)
            ->and($authority->ids)->toBe([$material['public_id']]);
        expect(Artisan::call('runa:auth:initialize', ['--email' => 'operator-cli@example.test', '--name' => 'Operator', '--output' => $file, '--confirm' => true]))->toBe(1);
        expect(json_decode(file_get_contents($file), true)['token'])->toBe($material['token']);
        config()->set('auth.operator_recovery_enabled', false);
        expect(fn () => app(Passwords::class)->reset($material['token'], 'disabled-operator-password'))->toThrow(RuntimeException::class);
        config()->set('auth.operator_recovery_enabled', true);
        CarbonImmutable::setTestNow(CarbonImmutable::now('UTC')->addMinutes(16));
        expect(fn () => app(Passwords::class)->reset($material['token'], 'expired-operator-password'))->toThrow(OperationRejected::class);
    } finally {
        if (is_file($file)) {
            unlink($file);
        }
        rmdir($directory);
    }
})->group('auth-policy-operators');

function policyStepUpProof($test, User $actor, string $action, string $code): void
{
    $test->postJson('/api/runa/auth/step-up', ['action' => $action, 'requirement' => 'both', 'current_password' => 'a-long-test-password', 'method' => 'recovery', 'code' => $code])->assertOk();
    app()->instance('request', credentialRequest('/'));
}

it('expires and revalidates only allowlisted account exceptions with monotonic versions', function (): void {
    $actor = profileVerified('exception-actor@example.test');
    $target = entryUser('exception-target@example.test');
    entryLogin($this, ['identifier' => $actor->email, 'password' => 'a-long-test-password'])->assertOk();
    [$secret, $codes] = credentialEnroll($this, $actor);
    $policies = app(Policies::class);
    $revision = app(ModuleSettings::class)->read('runa.auth')['revision'];
    $scope = app(PrincipalScope::class);
    $set = fn ($value, $version) => $scope->run(new TrustedPrincipal($actor->public_id), fn () => $policies->exception($target->public_id, 'session_idle_minutes', $value, 'Approved temporary extension', now()->addHours(1)->toIso8601String(), $version, $revision));
    policyStepUpProof($this, $actor, 'runa.auth.policy.exception', $codes[0]);
    expect($set(240, 0)['version'])->toBe(1)
        ->and(app(PolicyExceptions::class)->value($target, 'session_idle_minutes'))->toBe(240);
    policyStepUpProof($this, $actor, 'runa.auth.policy.exception', $codes[1]);
    expect($set(null, 1)['version'])->toBe(2);
    policyStepUpProof($this, $actor, 'runa.auth.policy.exception', $codes[2]);
    expect($set(300, 2)['version'])->toBe(3);
    policyStepUpProof($this, $actor, 'runa.auth.policy.exception', $codes[3]);
    expect(fn () => $set(400, 1))->toThrow(OperationRejected::class);
    expect(fn () => $policies->exception($target->public_id, 'two_factor_required', 1, 'Forbidden', now()->addHour()->toIso8601String(), 0, $revision))->toThrow(InvalidArgumentException::class);
    // Current global policy invalidates old exceptions without waiting for a scheduler.
    entryPolicy(['notice_new_browser' => true]);
    expect(app(PolicyExceptions::class)->value($target, 'session_idle_minutes'))->toBe(120)
        ->and(DB::table('runa_auth.policy_exceptions')->whereNull('revoked_at')->count())->toBe(0);
})->group('auth-policy-operators');

it('rejects stale bulk revocation previews and rolls back proof and sessions with Audit', function (): void {
    app()->bind(BulkUserSelection::class, fn () => new class implements BulkUserSelection
    {
        public function authorize(SessionSelection $selection): void {}
    });
    $actor = profileVerified('bulk-actor@example.test');
    $target = profileVerified('bulk-target@example.test');
    entryLogin($this, ['identifier' => $target->email, 'password' => 'a-long-test-password'])->assertOk();
    entryLogin($this, ['identifier' => $actor->email, 'password' => 'a-long-test-password'])->assertOk();
    [$secret, $codes] = credentialEnroll($this, $actor);
    $action = app(BulkSessions::class);
    $selection = new SessionSelection([$target->public_id]);
    $preview = $action->preview($selection);
    expect($preview['sessions'])->toBe(1);
    policyStepUpProof($this, $actor, 'runa.auth.sessions.bulk_revoke', $codes[0]);
    $revoke = fn ($hash) => app(PrincipalScope::class)->run(new TrustedPrincipal($actor->public_id), fn () => $action->revoke($selection, false, $hash));
    expect(fn () => $revoke(str_repeat('0', 64)))->toThrow(OperationRejected::class);
    DB::statement('ALTER TABLE runa_audit.events ADD CONSTRAINT bulk_fixture_refusal CHECK (false) NOT VALID');
    expect(fn () => $revoke($preview['fingerprint']))->toThrow(AuditUnavailable::class);
    expect(DB::table('runa_auth.sessions')->where('user_id', $target->id)->whereNull('revoked_at')->count())->toBe(1)
        ->and(DB::table('runa_auth.step_up_proofs')->where('action', 'runa.auth.sessions.bulk_revoke')->value('consumed_at'))->toBeNull();
    DB::statement('ALTER TABLE runa_audit.events DROP CONSTRAINT bulk_fixture_refusal');
    expect($revoke($preview['fingerprint'])['sessions'])->toBe(1)
        ->and(DB::table('runa_auth.sessions')->where('user_id', $target->id)->whereNull('revoked_at')->count())->toBe(0);
})->group('auth-policy-operators');

it('consumes policy publication proof only with history and preserves scheduled deactivation', function (): void {
    $actor = profileVerified('policy-publisher@example.test');
    $target = profileVerified('scheduled-target@example.test');
    entryLogin($this, ['identifier' => $actor->email, 'password' => 'a-long-test-password'])->assertOk();
    [$secret, $codes] = credentialEnroll($this, $actor);
    $settings = app(ModuleSettings::class);
    $state = $settings->read('runa.auth');
    $publish = fn () => app(PrincipalScope::class)->run(new TrustedPrincipal($actor->public_id), fn () => app(Policies::class)->publish(['notice_new_browser' => true], $state['revision'], $state['generation']));
    app()->instance('request', credentialRequest('/'));
    expect($publish)->toThrow(OperationRejected::class);
    expect($settings->read('runa.auth')['revision'])->toBe($state['revision']);
    policyStepUpProof($this, $actor, 'runa.auth.policy.publish', $codes[0]);
    DB::statement('ALTER TABLE runa_auth.policy_versions ADD CONSTRAINT publication_history_refusal CHECK (false) NOT VALID');
    expect($publish)->toThrow(QueryException::class);
    expect(DB::table('runa_auth.step_up_proofs')->where('action', 'runa.auth.policy.publish')->value('consumed_at'))->toBeNull();
    DB::statement('ALTER TABLE runa_auth.policy_versions DROP CONSTRAINT publication_history_refusal');
    expect($publish()['status'])->toBe('succeeded');
    expect(DB::table('runa_auth.step_up_proofs')->where('action', 'runa.auth.policy.publish')->value('consumed_at'))->not->toBeNull();
    DB::transaction(function () use ($target): void {
        $target->active = false;
        $target->activate_at = now()->subMinute();
        $target->deactivate_at = now()->addHour();
        $target->save();
    });
    $lifecycle = app(ScheduledLifecycle::class);
    $lifecycle->run();
    $updated = User::find($target->id);
    expect($updated->active)->toBeTrue()->and($updated->activate_at)->toBeNull()->and($updated->deactivate_at)->not->toBeNull();
    $count = DB::table('runa_audit.events')->count();
    $lifecycle->run();
    expect(DB::table('runa_audit.events')->count())->toBe($count);
    CarbonImmutable::setTestNow(CarbonImmutable::now('UTC')->addHours(2));
    expect(app(LookupIdentity::class)->eligibility($target->public_id)->mayAuthenticate())->toBeFalse();
    $lifecycle->run();
    expect(User::find($target->id)->active)->toBeFalse();
})->group('auth-policy-operators');

it('session cleanup and deadline projection respect unexpired account lifetime exceptions', function (): void {
    entryPolicy(['session_idle_minutes' => 1]);
    $actor = profileVerified('cleanup-actor@example.test');
    $target = profileVerified('cleanup-target@example.test');
    entryLogin($this, ['identifier' => $target->email, 'password' => 'a-long-test-password'])->assertOk();
    $session = DB::table('runa_auth.sessions')->where('user_id', $target->id)->first();
    entryLogin($this, ['identifier' => $actor->email, 'password' => 'a-long-test-password'])->assertOk();
    [$secret, $codes] = credentialEnroll($this, $actor);
    $revision = app(ModuleSettings::class)->read('runa.auth')['revision'];
    policyStepUpProof($this, $actor, 'runa.auth.policy.exception', $codes[0]);
    app(PrincipalScope::class)->run(new TrustedPrincipal($actor->public_id), fn () => app(Policies::class)
        ->exception($target->public_id, 'session_idle_minutes', 30, 'Temporary extension', now()->addMinutes(10)->toIso8601String(), 0, $revision));
    CarbonImmutable::setTestNow(CarbonImmutable::now('UTC')->addMinutes(2));
    expect(app(SessionStore::class)->live($session))->toBeTrue();
    app(CleanupSessions::class)->run();
    expect(DB::table('runa_auth.sessions')->where('id', $session->id)->exists())->toBeTrue();
    CarbonImmutable::setTestNow(CarbonImmutable::now('UTC')->addMinutes(9));
    expect(app(SessionStore::class)->live($session))->toBeFalse();
    app(CleanupSessions::class)->run();
    expect(DB::table('runa_auth.sessions')->where('id', $session->id)->exists())->toBeFalse();
})->group('auth-policy-operators');

it('persists welcome policy defaults and redirects only the public root through named sign-in', function (): void {
    $settings = app(ModuleSettings::class);
    expect($settings->value('runa.auth', 'public_welcome_enabled'))->toBeTrue();
    entryPolicy(['public_welcome_enabled' => false]);
    $this->getJson('/api/runa/auth/entry-options')->assertOk()->assertJsonPath('data.public_welcome_enabled', false);
    $this->get('/')->assertRedirect(route('runa.auth.pages.login'));
    // The setting never invalidates a session or bypasses its restricted-state resolver.
    $user = profileVerified('welcome-setting@example.test');
    entryLogin($this, ['identifier' => $user->email, 'password' => 'a-long-test-password'])->assertOk();
    $this->get('/')->assertRedirect(route('runa.auth.pages.login'));
    $this->getJson('/api/runa/auth/session')->assertOk()->assertJsonPath('data.restrictions', []);
    $this->postJson('/api/runa/auth/logout')->assertOk();
    $this->getJson('/api/runa/auth/session')->assertUnauthorized();
    foreach (['/app' => 'application', '/account' => 'account'] as $path => $name) {
        expect(app('router')->getRoutes()->match(Request::create($path))->getName())->toBe('runa.auth.pages.'.$name);
    }
    entryPolicy(['public_welcome_enabled' => true]);
    expect($settings->value('runa.auth', 'public_welcome_enabled'))->toBeTrue();
})->group('auth-account-ui');
