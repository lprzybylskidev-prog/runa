<?php

use Carbon\CarbonImmutable;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Runa\Contracts\Modules\PreparationParticipant;
use Runa\Integration\Core\AdminDiagnostics;
use Runa\Integration\Core\AdminHttpGuard;
use Runa\ModuleFramework\Api\ModuleDeclarations;
use Runa\ModuleFramework\Api\ModuleHttp;
use Runa\ModuleFramework\Api\OperationGuard;
use Runa\ModuleFramework\LifecyclePolicies;
use Runa\ModuleFramework\ModuleAvailability;
use Runa\ModuleFramework\ModuleHealth;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\ModuleFramework\Updates\ModuleUpdates;
use Runa\Modules\Admin\Contracts\AdminAccess;
use Runa\Modules\Auth\Actions\ChangeIdentity;
use Runa\Modules\Auth\Contracts\NewIdentity;
use Runa\Modules\Auth\Models\User;
use Runa\Runtime\MaintenanceState;
use Runa\Support\Modules\Api\ModuleApis;
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
