<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Runa\Contracts\Modules\AuthorityGuard;
use Runa\Contracts\Modules\OperationAuthorizer;
use Runa\Contracts\Modules\OperationContext;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\Modules\Audit\Contracts\AuditUnavailable;
use Runa\Modules\Auth\Actions\ChangeIdentity;
use Runa\Modules\Auth\Actions\PolicyHistory;
use Runa\Modules\Auth\Contracts\AuthIdentities;
use Runa\Modules\Auth\Contracts\AuthLifecycle;
use Runa\Modules\Auth\Contracts\IdentityConflict;
use Runa\Modules\Auth\Contracts\LifecycleChange;
use Runa\Modules\Auth\Contracts\NewIdentity;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Queries\LookupIdentity;
use Runa\Support\Modules\Api\DenyAuthorityChanges;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationGuard;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Api\PrincipalScope;
use Runa\Support\Modules\Development\ModuleContractSuite;
use Runa\Support\Modules\ModuleRegistry;
use Runa\Support\Modules\Settings\ModuleSettings;
use Runa\Support\Modules\Updates\ModuleUpdates;

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
        DB::table('shared.modules')->where('module_id', 'runa.'.$code)->update(['installed_version' => $code === 'auth' ? '0.9.0' : '0.3.0']);
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
    app()->bind(AuthorityGuard::class, fn () => new class implements AuthorityGuard
    {
        public function assertMayRemove(Connection $connection, array $publicIds): void
        {
            expect($connection)->toBe(DB::connection());
            expect($connection->transactionLevel())->toBeGreaterThan(0);
        }
    });
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
    while (DB::connection()->transactionLevel() > 0) {
        DB::rollBack();
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
});

it('normalizes Auth names and identifiers and enforces PostgreSQL naming and claim invariants', function (): void {
    $actions = app(ChangeIdentity::class);
    $one = $actions->create(new NewIdentity('  One@Example.test ', name: 'One Person', login: ' First.Login '));
    $two = $actions->create(new NewIdentity('two@example.test', firstName: 'Druga', surname: 'Osoba'));
    $lookup = app(LookupIdentity::class);
    expect($lookup->forIdentifier(' ONE@EXAMPLE.TEST ')->public_id)->toBe($one->publicId)
        ->and($two->displayName)->toBe('Druga Osoba');
    $settings = app(ModuleSettings::class);
    foreach (['login', 'either', 'email'] as $mode) {
        $current = $settings->read('runa.auth');
        expect($settings->update('runa.auth', ['identifier_mode' => $mode], $current['revision'], $current['generation'])->status)->toBe('succeeded');
        expect($lookup->forIdentifier($mode === 'email' ? 'one@example.test' : ' FIRST.LOGIN ')?->public_id)->toBe($one->publicId);
    }
    foreach ([fn () => new NewIdentity('x@example.test'), fn () => new NewIdentity('x@example.test', name: 'X', firstName: 'Y', surname: 'Z'),
        fn () => new NewIdentity('x@example.test', firstName: 'X'), fn () => new NewIdentity('x@example.test', name: ' '),
        fn () => new NewIdentity('x@example.test', name: 'X', login: 'x@example.test')] as $invalid) {
        expect($invalid)->toThrow(InvalidArgumentException::class);
    }
    expect(fn () => $actions->create(new NewIdentity('ONE@example.test', name: 'Duplicate')))->toThrow(IdentityConflict::class, 'identifier_conflict');
    expect(fn () => $actions->create(new NewIdentity('other@example.test', name: 'Duplicate', login: 'FIRST.LOGIN')))->toThrow(IdentityConflict::class);
    foreach ([['name' => '', 'first_name' => null], ['first_name' => 'mixed'], ['email_claim' => null], ['login_claim' => 'different']] as $invalid) {
        expect(fn () => DB::transaction(fn () => DB::table('runa_auth.users')->where('public_id', $one->publicId)->update($invalid)))->toThrow(QueryException::class);
    }
    $model = User::where('public_id', $one->publicId)->first();
    expect($model->getAuthIdentifier())->toBe($one->publicId)->and($model->toArray())->toBe(['public_id' => $one->publicId]);
    $model->public_id = $two->publicId;
    expect(fn () => DB::transaction(fn () => $model->saveQuietly()))->toThrow(LogicException::class, 'immutable');
});

it('evaluates independent Auth predicates at exact UTC boundaries without scheduler or cached state', function (): void {
    CarbonImmutable::setTestNow('2026-10-23T10:00:00Z');
    $actions = app(ChangeIdentity::class);
    $identity = $actions->create(new NewIdentity('states@example.test', name: 'States'));
    $lookup = app(LookupIdentity::class);
    expect($lookup->eligibility($identity->publicId)->blockers)->toBe(['approval_required', 'inactive']);
    $change = function (LifecycleChange $change) use (&$identity, $actions): void {
        $identity = $actions->change($identity->publicId, $identity->version, $change);
    };
    $change(new LifecycleChange('approve'));
    $change(new LifecycleChange('schedule_activation', '2026-10-23T11:00:00Z'));
    $change(new LifecycleChange('schedule_deactivation', '2026-10-23T13:00:00Z'));
    $change(new LifecycleChange('set_expiry', '2026-10-23T12:00:00Z'));
    $change(new LifecycleChange('set_security_lock', enabled: true));
    $change(new LifecycleChange('set_attempt_lock', '2026-10-23T11:30:00Z'));
    $change(new LifecycleChange('require_password_change', enabled: true));
    expect($lookup->eligibility($identity->publicId)->blockers)->toBe(['before_activation', 'security_locked', 'attempt_locked']);
    CarbonImmutable::setTestNow('2026-10-23T12:00:00Z');
    expect($lookup->eligibility($identity->publicId)->blockers)->toBe(['expired', 'security_locked']);
    $change(new LifecycleChange('set_security_lock', enabled: false));
    $change(new LifecycleChange('set_expiry'));
    expect($lookup->eligibility($identity->publicId)->mayAuthenticate())->toBeTrue()
        ->and($lookup->eligibility($identity->publicId)->restrictions)->toBe(['email_verification_required', 'password_change_required'])
        ->and($lookup->eligibility($identity->publicId)->mayOperate())->toBeFalse();
    CarbonImmutable::setTestNow('2026-10-23T13:00:00Z');
    expect($lookup->eligibility($identity->publicId)->blockers)->toBe(['deactivated']);
    $revokedGeneration = $lookup->eligibility($identity->publicId)->securityVersion;
    $change(new LifecycleChange('schedule_deactivation'));
    expect($lookup->eligibility($identity->publicId)->mayAuthenticate())->toBeTrue()
        ->and($lookup->eligibility($identity->publicId)->securityVersion)->toBeGreaterThan($revokedGeneration);
    $change(new LifecycleChange('deactivate'));
    CarbonImmutable::setTestNow('2026-10-24T13:00:00Z');
    expect($lookup->eligibility($identity->publicId)->blockers)->toBe(['inactive']);
});

it('reserves deleted identifiers by default and refuses conflicting restoration without transferring identity or history', function (): void {
    $actions = app(ChangeIdentity::class);
    $old = $actions->create(new NewIdentity('reserved@example.test', name: 'Old', login: 'reserved', active: true));
    $old = $actions->change($old->publicId, $old->version, new LifecycleChange('delete'));
    expect(fn () => $actions->create(new NewIdentity('RESERVED@example.test', name: 'Other')))->toThrow(IdentityConflict::class);
    $old = $actions->change($old->publicId, $old->version, new LifecycleChange('restore'));
    $user = User::where('public_id', $old->publicId)->first();
    expect($user->active)->toBeFalse()->and($user->security_version)->toBe(3)->and($user->remember_token)->toBeNull();
    $settings = app(ModuleSettings::class);
    $current = $settings->read('runa.auth');
    $settings->update('runa.auth', ['reuse_deleted_identifiers' => true], $current['revision'], $current['generation']);
    $old = $actions->change($old->publicId, $old->version, new LifecycleChange('delete'));
    $new = $actions->create(new NewIdentity('reserved@example.test', name: 'New', login: 'reserved'));
    expect($new->publicId)->not->toBe($old->publicId);
    expect(fn () => $actions->change($old->publicId, $old->version, new LifecycleChange('restore')))->toThrow(IdentityConflict::class, 'identifier_conflict');
    expect(User::withTrashed()->where('public_id', $old->publicId)->first()->trashed())->toBeTrue()
        ->and(app(LookupIdentity::class)->forIdentifier('reserved@example.test')->public_id)->toBe($new->publicId);
    $entities = DB::table('runa_audit.entities')->where('entity_public_id', $old->publicId)->count();
    expect($entities)->toBeGreaterThan(3)->and(app(LookupIdentity::class)->identity($old->publicId)->displayName)->toBe('Old');
});

it('keeps Auth lifecycle and revocation atomic when Audit fails even inside a caught savepoint', function (): void {
    $actions = app(ChangeIdentity::class);
    $identity = $actions->create(new NewIdentity('atomic@example.test', name: 'Atomic', active: true));
    $count = DB::table('runa_audit.events')->count();
    DB::transaction(function () use ($identity, $actions, $count): void {
        DB::statement('ALTER TABLE runa_audit.events ADD CONSTRAINT auth_fixture_refusal CHECK (false) NOT VALID');
        expect(fn () => $actions->change($identity->publicId, $identity->version, new LifecycleChange('deactivate')))->toThrow(AuditUnavailable::class);
        $user = User::where('public_id', $identity->publicId)->first();
        expect($user->active)->toBeTrue()->and($user->version)->toBe(1)->and($user->security_version)->toBe(1)
            ->and(DB::table('runa_audit.events')->count())->toBe($count);
        DB::statement('ALTER TABLE runa_audit.events DROP CONSTRAINT auth_fixture_refusal');
    });
    $identity = $actions->change($identity->publicId, 1, new LifecycleChange('deactivate'));
    $identity = $actions->change($identity->publicId, $identity->version, new LifecycleChange('activate'));
    expect(app(LookupIdentity::class)->eligibility($identity->publicId)->securityVersion)->toBe(3);
    expect(fn () => $actions->change($identity->publicId, 1, new LifecycleChange('delete')))->toThrow(IdentityConflict::class, 'identity_stale');
    expect(DB::table('runa_audit.events')->orderByDesc('id')->value('outcome'))->toBe('refused');
    expect(DB::table('runa_audit.events')->pluck('changes')->implode(' '))->not->toContain('atomic@example.test');
});

it('denies Auth public reads and writes before Permissions and denies lifecycle without a trusted invariant participant', function (): void {
    $apis = app(ModuleApis::class);
    $identities = $apis->resolve('runa.auth', AuthIdentities::class, '1.0.0');
    $lifecycle = $apis->resolve('runa.auth', AuthLifecycle::class, '1.0.0');
    $identity = app(ChangeIdentity::class)->create(new NewIdentity('private@example.test', name: 'Private'));
    expect(fn () => $identities->identity($identity->publicId))->toThrow(OperationRejected::class)
        ->and(fn () => $identities->eligibility($identity->publicId))->toThrow(OperationRejected::class)
        ->and(fn () => $lifecycle->create(new NewIdentity('denied@example.test', name: 'Denied')))->toThrow(OperationRejected::class);
    app()->bind(AuthorityGuard::class, DenyAuthorityChanges::class);
    expect(fn () => app(ChangeIdentity::class)->change($identity->publicId, 1, new LifecycleChange('activate')))->toThrow(IdentityConflict::class, 'authority_change_refused');
    expect(User::where('public_id', $identity->publicId)->first()->active)->toBeFalse();
    app()->bind(OperationAuthorizer::class, fn () => new class implements OperationAuthorizer
    {
        public function allows(OperationContext $context, string $permission): bool
        {
            return in_array($permission, ['runa.auth.identities.read', 'runa.auth.eligibility.read'], true)
                && app(PrincipalScope::class)->current() !== null;
        }
    });
    app()->forgetInstance(OperationGuard::class);
    app()->forgetInstance(ModuleApis::class);
    $identities = app(ModuleApis::class)->resolve('runa.auth', AuthIdentities::class, '1.0.0');
    app(PrincipalScope::class)->run(new TrustedPrincipal($identity->publicId), function () use ($identities, $identity): void {
        $projection = $identities->identity($identity->publicId);
        expect($projection)->toEqual($identity)
            ->and($identities->eligibility($identity->publicId)->blockers)->toBe(['approval_required', 'inactive']);
        expect(fn () => $projection->displayName = 'forged')->toThrow(Error::class);
        expect(json_encode($projection))->not->toContain('private@example.test');
    });
});

it('arbitrates competing Auth identifier claims in PostgreSQL and preserves immutable internal identity types', function (): void {
    config(['database.connections.auth_competitor' => config('database.connections.pgsql')]);
    $other = DB::connection('auth_competitor');
    DB::beginTransaction();
    $identity = app(ChangeIdentity::class)->create(new NewIdentity('race@example.test', name: 'First'));
    $other->statement("SET lock_timeout = '100ms'");
    $attributes = User::where('public_id', $identity->publicId)->first()->getAttributes();
    unset($attributes['id']);
    $attributes['public_id'] = (string) Str::uuid7();
    try {
        // A non-cooperating writer still cannot bypass the uncommitted unique claim.
        expect(fn () => $other->table('runa_auth.users')->insert($attributes))->toThrow(QueryException::class, 'lock timeout');
        DB::commit();
        expect(fn () => $other->table('runa_auth.users')->insert($attributes))->toThrow(QueryException::class, 'duplicate key');
    } finally {
        $other->statement('RESET lock_timeout');
    }
    $column = DB::selectOne("SELECT is_identity, data_type FROM information_schema.columns WHERE table_schema = 'runa_auth' AND table_name = 'users' AND column_name = 'id'");
    expect($column->is_identity)->toBe('YES')->and($column->data_type)->toBe('bigint');
});

it('initializes Audit then Auth exactly once through the real fenced module updater with current runtime declarations', function (): void {
    foreach (['operator_tokens', 'initialization', 'policy_exceptions', 'policy_versions', 'notice_scan', 'known_browsers', 'notice_frequencies', 'security_notices', 'delivery_preferences', 'preferences', 'profile_mail', 'email_changes', 'secondary_emails', 'avatar_files', 'step_up_proofs', 'password_history', 'login_windows', 'sessions', 'trusted_devices'] as $table) {
        DB::statement('DROP TABLE runa_auth.'.$table.' RESTRICT');
    }
    DB::statement('DROP TABLE runa_auth.entry_tokens RESTRICT');
    DB::statement('DROP TABLE runa_auth.users RESTRICT');
    DB::statement('DROP SCHEMA runa_auth RESTRICT');
    DB::statement('DROP TABLE runa_audit.entities RESTRICT');
    DB::statement('DROP TABLE runa_audit.events RESTRICT');
    DB::statement('DROP SCHEMA runa_audit RESTRICT');
    DB::table('shared.modules')->whereIn('module_id', ['runa.audit', 'runa.auth'])->update(['installed_version' => null]);
    DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    $updates = app(ModuleUpdates::class);
    $updates->apply('runa', str_repeat('a', 40), static function (): void {});
    $events = DB::table('runa_audit.events')->count();
    $updates->apply('runa', str_repeat('a', 40), static function (): void {});
    expect(DB::table('runa_audit.events')->count())->toBe($events)->and(DB::table('runa_auth.users')->count())->toBe(0);
    $record = app(ModuleRegistry::class)->snapshot()->modules['runa.auth'];
    expect($record->installedVersion)->toBe('0.9.0')->and((new ModuleContractSuite(base_path(), true))->runtime($record))->toBe([]);
});
