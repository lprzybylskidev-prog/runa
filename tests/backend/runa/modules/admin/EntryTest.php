<?php

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Runa\Contracts\Modules\PreparationParticipant;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\Integration\Core\AdminDiagnostics;
use Runa\Integration\Core\AdminHttpGuard;
use Runa\ModuleFramework\Api\ModuleDeclarations;
use Runa\ModuleFramework\Api\ModuleHttp;
use Runa\ModuleFramework\Api\OperationGuard;
use Runa\ModuleFramework\Api\PrincipalScope;
use Runa\ModuleFramework\LifecyclePolicies;
use Runa\ModuleFramework\ModuleAvailability;
use Runa\ModuleFramework\ModuleHealth;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\ModuleFramework\Updates\ModuleUpdates;
use Runa\Modules\Admin\Contracts\AdminAccess;
use Runa\Modules\Audit\Contracts\AuditUnavailable;
use Runa\Modules\Auth\Actions\ChangeIdentity;
use Runa\Modules\Auth\Actions\ProfileMutation;
use Runa\Modules\Auth\Actions\Profiles;
use Runa\Modules\Auth\Contracts\AuthProfiles;
use Runa\Modules\Auth\Contracts\NewIdentity;
use Runa\Modules\Auth\Contracts\ProfileChange;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Permissions\Actions\ChangeAssignments;
use Runa\Modules\Permissions\Contracts\AssignmentSet;
use Runa\Modules\Permissions\Contracts\PermissionsAccess;
use Runa\Runtime\MaintenanceState;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Data\ModuleTransactions;
use Runa\Support\Modules\Development\ModuleContractSuite;
use Runa\Support\Modules\Settings\ModuleSettings;
use Tests\Fixtures\CoreModuleStorage;
use Tests\PlatformTestCase;

uses(PlatformTestCase::class);

beforeEach(function (): void {
    // Exercise the specified target runtime without prematurely changing platform VERSION.
    // Existing source ranges explicitly support both the released and target runtimes.
    foreach ([MaintenanceState::class, OperationGuard::class, ModuleApis::class] as $service) {
        app()->forgetInstance($service);
    }
    app()->singleton(ModuleAvailability::class, fn () => new ModuleAvailability(
        app(ModuleRegistry::class), app(ModuleHealth::class), app(LifecyclePolicies::class), '0.7.0',
    ));
    foreach (['runa_auth', 'runa_audit', 'runa_permissions'] as $schema) {
        if (DB::selectOne('SELECT 1 FROM pg_namespace WHERE nspname = ?', [$schema])) {
            throw new RuntimeException('Admin fixture requires absent disposable Core schemas.');
        }
    }
    $this->adminStorageOwned = true;
    $updates = new ModuleUpdates(DB::connection(), app(ModuleRegistry::class), base_path(), '0.7.0', true, app(PreparationParticipant::class));
    $updates->apply('runa', str_repeat('a', 40), static function (): void {});
    config()->set('queue.default', 'database');
    config()->set('hashing.bcrypt.rounds', 4);
    $settings = app(ModuleSettings::class)->read('runa.auth');
    app(ModuleSettings::class)->update('runa.auth', ['email_verification' => 'disabled'], $settings['revision'], $settings['generation']);
    $identity = app(ChangeIdentity::class)->create(new NewIdentity('admin-entry@example.test', name: 'Admin entry', active: true, approved: true));
    $this->adminUser = User::where('public_id', $identity->publicId)->firstOrFail();
    DB::transaction(function (): void {
        $this->adminUser->password = Hash::make('admin-entry-password');
        $this->adminUser->save();
    });
    $routes = new Router(app('events'), app());
    (new ModuleHttp(app(ModuleRegistry::class), app(ModuleDeclarations::class), $routes, DB::connection()))->register();
    foreach ($routes->getRoutes() as $route) {
        app('router')->getRoutes()->add($route);
    }
    app('router')->getRoutes()->refreshNameLookups();
    $this->adminGrant = function (string $code): void {
        $id = DB::table('runa_permissions.permissions')->where('code', $code)->value('id');
        expect($id)->not->toBeNull();
        DB::table('runa_permissions.user_permissions')->insertOrIgnore([
            'user_public_id' => $this->adminUser->public_id, 'permission_id' => $id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    };
    $this->adminLogin = function (): void {
        $this->postJson('/api/runa/auth/login', ['identifier' => 'admin-entry@example.test', 'password' => 'admin-entry-password'])->assertOk();
        $this->withCredentials()->withCookie('runa_session', app('session')->getId());
    };
    $this->adminProof = function (string $action = 'runa.admin.enter'): void {
        $this->postJson('/api/runa/auth/step-up', ['action' => $action, 'requirement' => 'password', 'current_password' => 'admin-entry-password'])->assertOk();
    };
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
    if (! ($this->adminStorageOwned ?? false)) {
        return;
    }
    while (DB::connection()->transactionLevel() > 0) {
        DB::rollBack();
    }
    $authId = DB::table('shared.modules')->where('module_id', 'runa.auth')->value('id');
    foreach (DB::table('shared.module_work')->where('module_id', $authId)->pluck('public_id') as $workId) {
        DB::table('shared.jobs')->whereRaw("payload::jsonb ->> 'runa_work' = ?", [$workId])->delete();
    }
    DB::table('shared.module_work')->where('module_id', $authId)->delete();
    CoreModuleStorage::removePermissions();
    foreach (['auth', 'audit'] as $code) {
        foreach (CoreModuleStorage::TABLES[$code] as $table) {
            DB::statement('DROP TABLE IF EXISTS runa_'.$code.'.'.$table.' RESTRICT');
        }
        DB::statement('DROP SCHEMA IF EXISTS runa_'.$code.' RESTRICT');
        CoreModuleStorage::removeTransitions('runa.'.$code);
        $id = DB::table('shared.modules')->where('module_id', 'runa.'.$code)->value('id');
        DB::table('shared.module_update_steps')->where('module_id', $id)->delete();
        DB::table('shared.modules')->where('module_id', 'runa.'.$code)->delete();
    }
    DB::table('shared.module_registry_state')->where('id', 1)->update(['update_operation' => null, 'update_owner' => null, 'update_fingerprint' => null]);
    DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
});

it('admits a direct Admin entry grant only with current session-bound password proof', function (): void {
    ($this->adminLogin)();
    $this->getJson('/api/admin')->assertForbidden()->assertJsonPath('reason', 'module_forbidden');
    ($this->adminGrant)('runa.admin.enter');
    $this->getJson('/api/admin')->assertForbidden()->assertJsonPath('reason', 'step_up_required');
    ($this->adminProof)('app.unrelated');
    $this->getJson('/api/admin')->assertForbidden()->assertJsonPath('reason', 'step_up_required');
    ($this->adminProof)();
    $this->getJson('/api/admin')->assertOk()->assertJsonPath('data.schema_version', 1)
        ->assertJsonPath('data.owners', [])->assertHeader('Cache-Control', 'no-store, private');
    expect(DB::table('runa_permissions.user_roles')->count())->toBe(0)
        ->and(DB::table('runa_auth.step_up_proofs')->where('action', 'runa.admin.enter')->value('consumed_at'))->toBeNull();
    $this->getJson('/api/admin')->assertOk();
    CarbonImmutable::setTestNow(CarbonImmutable::now('UTC')->addSeconds(301));
    $this->getJson('/api/admin')->assertForbidden()->assertJsonPath('reason', 'step_up_required');
});

it('refuses anonymous and revoked entry authority without protected bootstrap values', function (): void {
    $this->getJson('/api/admin')->assertUnauthorized()->assertJsonMissingPath('data');
    ($this->adminGrant)('runa.admin.enter');
    ($this->adminLogin)();
    ($this->adminProof)();
    $this->getJson('/api/admin')->assertOk();
    DB::table('runa_permissions.user_permissions')->where('user_public_id', $this->adminUser->public_id)->delete();
    $this->getJson('/api/admin')->assertForbidden()->assertJsonMissingPath('data');
});

it('binds entry proof to its session and current security version', function (): void {
    ($this->adminGrant)('runa.admin.enter');
    ($this->adminLogin)();
    ($this->adminProof)();
    $this->getJson('/api/admin')->assertOk();
    DB::table('runa_auth.step_up_proofs')->where('action', 'runa.admin.enter')->update(['session_hash' => str_repeat('0', 64)]);
    $this->getJson('/api/admin')->assertForbidden()->assertJsonPath('reason', 'step_up_required');
    ($this->adminProof)();
    DB::table('runa_auth.step_up_proofs')->where('action', 'runa.admin.enter')->increment('security_version');
    $this->getJson('/api/admin')->assertForbidden()->assertJsonPath('reason', 'step_up_required');
});

it('keeps unavailable Core and maintenance blocked with valid entry authority', function (): void {
    ($this->adminGrant)('runa.admin.enter');
    ($this->adminLogin)();
    ($this->adminProof)();
    $marker = app()->storagePath('framework/maintenance/enabled');
    if (file_exists($marker)) {
        throw new RuntimeException('Admin fixture refuses an existing maintenance marker.');
    }
    if (! is_dir(dirname($marker))) {
        mkdir(dirname($marker), 0775, true);
    }
    file_put_contents($marker, 'admin-entry-fixture');
    try {
        $this->getJson('/api/admin')->assertServiceUnavailable()->assertJsonMissingPath('data');
    } finally {
        unlink($marker);
    }
    foreach (['runa.auth', 'runa.permissions', 'runa.audit', 'runa.admin'] as $module) {
        $version = DB::table('shared.modules')->where('module_id', $module)->value('installed_version');
        DB::table('shared.modules')->where('module_id', $module)->update(['installed_version' => null]);
        $this->getJson('/api/admin')->assertServiceUnavailable()->assertJsonMissingPath('data');
        DB::table('shared.modules')->where('module_id', $module)->update(['installed_version' => $version]);
    }
});

it('installs Admin through explicit forward history and declares bounded guarded operations', function (): void {
    $suite = new ModuleContractSuite(base_path(), true);
    expect($suite->source('runa.admin'))->toBe([])
        ->and($suite->runtime(app(ModuleRegistry::class)->snapshot()->modules['runa.admin']))->toBe([])
        ->and(DB::table('shared.module_update_steps')->where('step', 'upgrade:initialize:install:0.1.0')->where('status', 'succeeded')->exists())->toBeTrue();
    $api = app(ModuleApis::class)->resolve('runa.admin', AdminAccess::class, '1.0.0');
    expect($api)->toBeInstanceOf(AdminAccess::class)
        ->and(app('router')->getRoutes()->getByName('runa.admin.bootstrap')->uri())->toBe('api/admin');
});

it('projects diagnostics through fixed source adapters without private metadata', function (): void {
    ($this->adminGrant)('runa.admin.enter');
    ($this->adminLogin)();
    ($this->adminProof)();
    // Exercise trusted adapters with the HTTP-resolved principal and request session.
    $captured = null;
    app('router')->get('/api/admin-diagnostics-fixture', function () use (&$captured) {
        $captured = app(AdminDiagnostics::class)->snapshot();

        return response()->json($captured);
    })->middleware(['api', AdminHttpGuard::class])->name('fixture.admin.diagnostics');
    $this->getJson('/api/admin-diagnostics-fixture')->assertForbidden();
    ($this->adminGrant)('runa.admin.diagnostics.read');
    $this->getJson('/api/admin-diagnostics-fixture')->assertOk();
    expect($captured['modules'])->toHaveCount(4);
    foreach ($captured['modules'] as $module) {
        expect(array_keys($module))->not->toContain('source_path', 'manifest_hash', 'settings', 'config', 'work', 'payload');
    }
});

function nativeMutationProof(object $test): void
{
    ($test->adminLogin)();
    $begin = $test->postJson('/api/runa/auth/security/totp-begin', ['current_password' => 'admin-entry-password'])->assertOk()->json('data');
    $code = app(Google2FA::class)->oathTotp($begin['secret'], intdiv(now()->getTimestamp(), 30));
    $codes = $test->postJson('/api/runa/auth/security/totp-confirm', ['current_password' => 'admin-entry-password', 'enrollment_code' => $code])->assertOk()->json('data.recovery_codes');
    $test->postJson('/api/runa/auth/step-up', ['action' => 'runa.permissions.access.manage', 'requirement' => 'both',
        'current_password' => 'admin-entry-password', 'method' => 'recovery', 'code' => $codes[0]])->assertOk();
    $request = Request::create('/', 'POST');
    $request->setLaravelSession(app('session')->driver());
    app()->instance('request', $request);
}

it('validates both owners before Auth first apply and commits profile assignments proof and Audit together', function (): void {
    foreach (['runa.auth.profiles.manage', 'runa.auth.eligibility.read', 'runa.permissions.access.manage'] as $code) {
        ($this->adminGrant)($code);
    }
    $target = app(ChangeIdentity::class)->create(new NewIdentity('native-mutation@example.test', name: 'Before', active: true, approved: true));
    nativeMutationProof($this);
    app(PrincipalScope::class)->run(new TrustedPrincipal($this->adminUser->public_id), function () use ($target): void {
        $api = app(ModuleApis::class)->resolve('runa.permissions', PermissionsAccess::class, '2.0.0');
        $read = DB::table('runa_permissions.permissions')->where('code', 'runa.audit.read')->value('public_id');
        $review = $api->previewAssignments($target->publicId, new AssignmentSet(permissions: [$read]));
        $assignment = app(ChangeAssignments::class)->mutation($review);
        $profile = new ProfileMutation(app(Profiles::class), $target->publicId, $target->version, new ProfileChange(name: 'After'));
        $results = app(ModuleTransactions::class)->run([$assignment, $profile]);
        expect(array_keys($results)[0])->toStartWith('runa.auth.profile.edit.')
            ->and($results['runa.permissions.'.$assignment->definition()->key]['identity_version'])->toBe($target->version + 1)
            ->and($results['runa.permissions.'.$assignment->definition()->key]['assigned']['permissions'])->toBe([$read])
            ->and(User::where('public_id', $target->publicId)->value('name'))->toBe('After')
            ->and(DB::table('runa_auth.step_up_proofs')->where('action', 'runa.permissions.access.manage')->value('consumed_at'))->not->toBeNull()
            ->and(DB::table('runa_audit.events')->where('action', 'profile.admin_changed')->count())->toBe(1)
            ->and(DB::table('runa_audit.events')->where('action', 'assignments.changed')->count())->toBe(1)
            ->and(DB::transactionLevel())->toBe(0);
    });
});

it('rolls back Auth first apply assignments and consumed proof before independent refusal Audit', function (): void {
    foreach (['runa.auth.profiles.manage', 'runa.auth.eligibility.read', 'runa.permissions.access.manage'] as $code) {
        ($this->adminGrant)($code);
    }
    $target = app(ChangeIdentity::class)->create(new NewIdentity('native-rollback@example.test', name: 'Before', active: true, approved: true));
    nativeMutationProof($this);
    app(PrincipalScope::class)->run(new TrustedPrincipal($this->adminUser->public_id), function () use ($target): void {
        $api = app(ModuleApis::class)->resolve('runa.permissions', PermissionsAccess::class, '2.0.0');
        $read = DB::table('runa_permissions.permissions')->where('code', 'runa.audit.read')->value('public_id');
        $review = $api->previewAssignments($target->publicId, new AssignmentSet(permissions: [$read]));
        $assignment = app(ChangeAssignments::class)->mutation($review);
        $profile = new ProfileMutation(app(Profiles::class), $target->publicId, $target->version, new ProfileChange(name: 'After'));
        DB::statement("ALTER TABLE runa_audit.events ADD CONSTRAINT native_mutation_refusal CHECK (action <> 'assignments.changed') NOT VALID");
        try {
            expect(fn () => app(ModuleTransactions::class)->run([$profile, $assignment]))->toThrow(AuditUnavailable::class);
        } finally {
            DB::statement('ALTER TABLE runa_audit.events DROP CONSTRAINT native_mutation_refusal');
        }
        expect(User::where('public_id', $target->publicId)->value('name'))->toBe('Before')
            ->and(User::where('public_id', $target->publicId)->value('version'))->toBe($target->version)
            ->and(DB::table('runa_permissions.user_access')->where('user_public_id', $target->publicId)->count())->toBe(0)
            ->and(DB::table('runa_auth.step_up_proofs')->where('action', 'runa.permissions.access.manage')->value('consumed_at'))->toBeNull()
            ->and(DB::table('runa_audit.events')->where('action', 'profile.admin_changed')->count())->toBe(0)
            ->and(DB::table('runa_audit.events')->where('action', 'access.refused')->count())->toBe(1)
            ->and(DB::transactionLevel())->toBe(0);
    });
});

it('rejects stale prewrite review and duplicate native writers or single use demands without partial changes', function (): void {
    foreach (['runa.auth.profiles.manage', 'runa.auth.eligibility.read', 'runa.permissions.access.manage'] as $code) {
        ($this->adminGrant)($code);
    }
    $target = app(ChangeIdentity::class)->create(new NewIdentity('native-stale@example.test', name: 'Before', active: true, approved: true));
    $other = app(ChangeIdentity::class)->create(new NewIdentity('native-other@example.test', name: 'Other', active: true, approved: true));
    nativeMutationProof($this);
    app(PrincipalScope::class)->run(new TrustedPrincipal($this->adminUser->public_id), function () use ($target, $other): void {
        $apis = app(ModuleApis::class);
        expect(fn () => $apis->resolve('runa.auth', AuthProfiles::class, '1.0.0'))->toThrow(OperationRejected::class);
        $api = $apis->resolve('runa.permissions', PermissionsAccess::class, '2.0.0');
        $read = DB::table('runa_permissions.permissions')->where('code', 'runa.audit.read')->value('public_id');
        $review = $api->previewAssignments($target->publicId, new AssignmentSet(permissions: [$read]));
        $second = $api->previewAssignments($other->publicId, new AssignmentSet(permissions: [$read]));
        $assignments = app(ChangeAssignments::class);
        $runner = app(ModuleTransactions::class);
        expect(fn () => $runner->run([$assignments->mutation($review), $assignments->mutation($second)]))->toThrow(OperationRejected::class);
        $profiles = app(Profiles::class);
        $change = new ProfileChange(name: 'After');
        expect(fn () => $runner->run([
            new ProfileMutation($profiles, $target->publicId, $target->version, $change),
            new ProfileMutation($profiles, $target->publicId, $target->version, $change),
        ]))->toThrow(OperationRejected::class);
        $updated = $apis->resolve('runa.auth', AuthProfiles::class, '2.0.0')->edit($target->publicId, $target->version, new ProfileChange(name: 'Intervening'));
        $profile = new ProfileMutation($profiles, $target->publicId, $updated->version, $change);
        expect(fn () => $runner->run([$profile, $assignments->mutation($review)]))->toThrow(OperationRejected::class);
        expect(User::where('public_id', $target->publicId)->value('name'))->toBe('Intervening')
            ->and(DB::table('runa_permissions.user_access')->whereIn('user_public_id', [$target->publicId, $other->publicId])->count())->toBe(0)
            ->and(DB::table('runa_auth.step_up_proofs')->where('action', 'runa.permissions.access.manage')->value('consumed_at'))->toBeNull();
        expect(fn () => $profile->identity())->toThrow(LogicException::class);
    });
});

it('fails closed when independent refusal Audit also fails and invalidates rolled back owner results', function (): void {
    foreach (['runa.auth.profiles.manage', 'runa.auth.eligibility.read', 'runa.permissions.access.manage'] as $code) {
        ($this->adminGrant)($code);
    }
    $target = app(ChangeIdentity::class)->create(new NewIdentity('native-refusal@example.test', name: 'Before', active: true, approved: true));
    nativeMutationProof($this);
    app(PrincipalScope::class)->run(new TrustedPrincipal($this->adminUser->public_id), function () use ($target): void {
        $api = app(ModuleApis::class)->resolve('runa.permissions', PermissionsAccess::class, '2.0.0');
        $read = DB::table('runa_permissions.permissions')->where('code', 'runa.audit.read')->value('public_id');
        $review = $api->previewAssignments($target->publicId, new AssignmentSet(permissions: [$read]));
        $assignment = app(ChangeAssignments::class)->mutation($review);
        $profile = new ProfileMutation(app(Profiles::class), $target->publicId, $target->version, new ProfileChange(name: 'After'));
        DB::statement("ALTER TABLE runa_audit.events ADD CONSTRAINT native_refusal_failure CHECK (action NOT IN ('assignments.changed', 'access.refused')) NOT VALID");
        try {
            try {
                app(ModuleTransactions::class)->run([$profile, $assignment]);
                $this->fail('Expected refused root.');
            } catch (OperationRejected $error) {
                expect($error->reason)->toBe('transaction_refusal_unavailable')->and($error->status)->toBe(503);
            }
            expect(User::where('public_id', $target->publicId)->value('name'))->toBe('Before')
                ->and(DB::table('runa_permissions.user_access')->where('user_public_id', $target->publicId)->count())->toBe(0)
                ->and(DB::table('runa_auth.step_up_proofs')->where('action', 'runa.permissions.access.manage')->value('consumed_at'))->toBeNull();
            expect(fn () => $profile->identity())->toThrow(LogicException::class);
            expect(fn () => $assignment->result())->toThrow(LogicException::class);
        } finally {
            DB::statement('ALTER TABLE runa_audit.events DROP CONSTRAINT native_refusal_failure');
        }
    });
});
