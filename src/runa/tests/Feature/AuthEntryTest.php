<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\FortifyServiceProvider;
use Runa\Contracts\Modules\AuthorityGuard;
use Runa\Modules\Audit\Contracts\AuditUnavailable;
use Runa\Modules\Auth\Actions\AuthenticateSession;
use Runa\Modules\Auth\Actions\ChangeIdentity;
use Runa\Modules\Auth\Actions\CleanupSessions;
use Runa\Modules\Auth\Actions\DeliverEntryMail;
use Runa\Modules\Auth\Actions\EntryTokens;
use Runa\Modules\Auth\Actions\ManageSessions;
use Runa\Modules\Auth\Actions\SessionStore;
use Runa\Modules\Auth\Contracts\AuthInvitations;
use Runa\Modules\Auth\Contracts\LifecycleChange;
use Runa\Modules\Auth\Contracts\NewIdentity;
use Runa\Modules\Auth\Models\User;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\ModuleHttp;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\ModuleRegistry;
use Runa\Support\Modules\Settings\ModuleSettings;
use Runa\Support\Modules\Updates\ModuleUpdates;

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

beforeEach(function (): void {
    $this->authSchemas = [];
    foreach (['runa_audit', 'runa_auth'] as $schema) {
        if (DB::selectOne('SELECT 1 FROM pg_namespace WHERE nspname = ?', [$schema])) {
            throw new RuntimeException('Auth fixture requires absent disposable schemas.');
        }
    }
    app(ModuleRegistry::class)->synchronize('runa');
    foreach (['audit' => 'events', 'auth' => 'identities'] as $code => $table) {
        $migration = require base_path('runa/modules/'.$code.'/database/migrations/2026_10_23_000001_runa_'.$code.'_'.$table.'.php');
        $migration->up();
        $this->authSchemas[] = 'runa_'.$code;
        DB::table('shared.modules')->where('module_id', 'runa.'.$code)->update(['installed_version' => $code === 'auth' ? '0.3.0' : '0.2.0']);
    }
    (require base_path('runa/modules/auth/database/migrations/2026_10_23_000002_runa_auth_entry_tokens.php'))->up();
    (require base_path('runa/modules/auth/database/migrations/2026_10_23_000003_runa_auth_sessions.php'))->up();
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
    while (DB::connection()->transactionLevel() > 0) {
        DB::rollBack();
    }
    $authId = DB::table('shared.modules')->where('module_id', 'runa.auth')->value('id');
    foreach (DB::table('shared.module_work')->where('module_id', $authId)->pluck('public_id') as $workId) {
        DB::table('shared.jobs')->whereRaw("payload::jsonb ->> 'runa_work' = ?", [$workId])->delete();
    }
    DB::table('shared.module_work')->where('module_id', $authId)->delete();
    // Exact fixture-created objects; deliberately no CASCADE or schema-wide reset.
    foreach (array_reverse($this->authSchemas ?? []) as $schema) {
        foreach ($schema === 'runa_auth' ? ['login_windows', 'sessions', 'trusted_devices', 'entry_tokens', 'users'] : ['entities', 'events'] as $table) {
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
    foreach (['runa_auth.login_windows', 'runa_auth.sessions', 'runa_auth.trusted_devices', 'runa_auth.entry_tokens', 'runa_auth.users', 'runa_audit.entities', 'runa_audit.events'] as $table) {
        DB::statement('DROP TABLE '.$table.' RESTRICT');
    }
    DB::statement('DROP SCHEMA runa_auth RESTRICT');
    DB::statement('DROP SCHEMA runa_audit RESTRICT');
    DB::table('shared.modules')->whereIn('module_id', ['runa.auth', 'runa.audit'])->update(['installed_version' => null]);
    app(ModuleUpdates::class)->apply('runa', str_repeat('b', 40), static function (): void {});
    $authId = DB::table('shared.modules')->where('module_id', 'runa.auth')->value('id');
    DB::table('shared.module_update_steps')->where('module_id', $authId)->whereIn('step', ['upgrade:entry:0.1.0:0.2.0', 'upgrade:sessions:0.2.0:0.3.0'])->delete();
    $old = ['identifier_mode' => 'either', 'reuse_deleted_identifiers' => true];
    DB::table('shared.modules')->where('module_id', 'runa.auth')->update(['installed_version' => '0.1.0', 'settings_revision' => 4,
        'settings_values' => app('encrypter')->encrypt(json_encode($old, JSON_THROW_ON_ERROR), false)]);
    $updates = app(ModuleUpdates::class);
    $updates->apply('runa', str_repeat('b', 40), static function (): void {});
    $state = app(ModuleSettings::class)->read('runa.auth');
    expect($state['settings']['identifier_mode']['value'])->toBe('either')
        ->and($state['settings']['public_registration']['value'])->toBeFalse()->and($state['revision'])->toBe(5);
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
