<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Fortify;
use Runa\Contracts\Modules\AuthorityGuard;
use Runa\Modules\Audit\Contracts\AuditUnavailable;
use Runa\Modules\Auth\Actions\ChangeIdentity;
use Runa\Modules\Auth\Actions\DeliverEntryMail;
use Runa\Modules\Auth\Actions\EntryTokens;
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
        DB::table('shared.modules')->where('module_id', 'runa.'.$code)->update(['installed_version' => '0.2.0']);
    }
    (require base_path('runa/modules/auth/database/migrations/2026_10_23_000002_runa_auth_entry_tokens.php'))->up();
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
        foreach ($schema === 'runa_auth' ? ['entry_tokens', 'users'] : ['entities', 'events'] as $table) {
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
    expect(Fortify::$registersRoutes)->toBeFalse();
    foreach (['login', 'register', 'forgot-password', 'two-factor-challenge'] as $path) {
        $this->postJson('/'.$path)->assertNotFound();
    }
    $csrf = $this->getJson('/api/runa/auth/csrf')->assertOk()->assertCookie('XSRF-TOKEN')->assertCookie('runa_session');
    $oldId = app('session')->getId();
    $this->postJson('/api/runa/auth/login', ['identifier' => 'session@example.test', 'password' => 'a-long-test-password'])
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
    $this->postJson('/api/runa/auth/login', ['identifier' => 'pending@example.test', 'password' => 'a-long-test-password'])
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
    $bad = $this->postJson('/api/runa/auth/login', ['identifier' => $user->email, 'password' => 'a-long-test-password'])->assertUnprocessable()->json();
    $this->postJson('/api/runa/auth/login', ['identifier' => 'absent@example.test', 'password' => 'a-long-test-password'])->assertUnprocessable()->assertExactJson($bad);
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
    $this->postJson('/api/runa/auth/login', ['identifier' => 'x@example.test', 'password' => 'no-password'])
        ->assertStatus(419)->assertJsonPath('error.code', 'csrf_token_mismatch');
});

it('upgrades encrypted identity settings additively only inside the reserved updater', function (): void {
    // Establish real migration/step history, then model the supported prior data version.
    foreach (['runa_auth.entry_tokens', 'runa_auth.users', 'runa_audit.entities', 'runa_audit.events'] as $table) {
        DB::statement('DROP TABLE '.$table.' RESTRICT');
    }
    DB::statement('DROP SCHEMA runa_auth RESTRICT');
    DB::statement('DROP SCHEMA runa_audit RESTRICT');
    DB::table('shared.modules')->whereIn('module_id', ['runa.auth', 'runa.audit'])->update(['installed_version' => null]);
    app(ModuleUpdates::class)->apply('runa', str_repeat('b', 40), static function (): void {});
    $authId = DB::table('shared.modules')->where('module_id', 'runa.auth')->value('id');
    DB::table('shared.module_update_steps')->where('module_id', $authId)->where('step', 'upgrade:entry:0.1.0:0.2.0')->delete();
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
