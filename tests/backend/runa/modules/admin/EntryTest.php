<?php

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
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
use Runa\ModuleFramework\Management\ApplicationManagement;
use Runa\ModuleFramework\Management\ManagementPages;
use Runa\ModuleFramework\ModuleAvailability;
use Runa\ModuleFramework\ModuleHealth;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\ModuleFramework\Updates\ModuleUpdates;
use Runa\Modules\Admin\Contracts\AdminAccess;
use Runa\Modules\Admin\Contracts\AdminRegistry;
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
use Tests\Fixtures\AggregateCompetition;
use Tests\Fixtures\CoreModuleStorage;
use Tests\Fixtures\ModuleSources;
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
    if (isset($this->adminAtomicDirectory)) {
        File::deleteDirectory($this->adminAtomicDirectory);
        DB::statement('DROP TABLE admin_fixture.notes RESTRICT');
        DB::statement('DROP SCHEMA admin_fixture RESTRICT');
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

it('registers lazy App declarations before guarded discovery and freezes replacement', function (): void {
    $sources = new ModuleSources;
    $namespace = 'Bootstrap'.bin2hex(random_bytes(6));
    $directory = $sources->root.'/app/Management/'.$namespace;
    mkdir($directory, 0775, true);
    foreach (['Provider', 'Handler'] as $name) {
        $file = $directory.'/'.$name.'.php';
        file_put_contents($file, str_replace('FIXTURE_NAMESPACE', $namespace, file_get_contents(__DIR__.'/../../Fixtures/Management/Admin'.$name.'.php.stub')));
        require $file;
    }
    $provider = 'App\\Management\\'.$namespace.'\\Provider';
    $handler = 'App\\Management\\'.$namespace.'\\Handler';
    $registrations = new ApplicationManagement(app(), $sources->root);
    app()->instance(ApplicationManagement::class, $registrations);
    app()->forgetInstance(ManagementPages::class);
    app()->bind($handler, $handler);
    $called = false;
    app()->afterResolving(AdminRegistry::class, function ($registry) use ($provider, &$called): void {
        $called = true;
        $registry->register(new $provider);
    });
    try {
        expect($called)->toBeFalse();
        ($this->adminGrant)('runa.admin.enter');
        ($this->adminLogin)();
        ($this->adminProof)();
        $this->getJson('/api/admin')->assertOk()->assertJsonPath('data.owners.0.id', 'app')
            ->assertJsonPath('data.owners.0.default_page', 'users')->assertJsonPath('data.dashboard.widgets', []);
        expect($called)->toBeTrue();
        $path = '/api/admin/app/users/sections/profile';
        $this->getJson($path.'?'.http_build_query(['kind' => 'record', 'limit' => 7, 'filters' => json_encode(['name' => null])]))
            ->assertOk()->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('data.values.name', 'Visible')->assertJsonPath('data.values.token.set', true)
            ->assertJsonMissingPath('data.values.hidden')->assertJsonMissingPath('data.values.unlisted');
        expect($handler::$lastRead->limit)->toBe(7)->and($handler::$lastRead->filters)->toBe(['name' => null]);
        foreach ([false, 12, '12', ''] as $value) {
            $this->getJson($path.'?'.http_build_query(['kind' => 'page', 'filters' => json_encode(['name' => $value]),
                'sort' => [['key' => 'name', 'direction' => 'desc']]]))->assertOk()->assertJsonPath('data.total', null);
            expect($handler::$lastRead->filters)->toBe(['name' => $value])->and($handler::$lastRead->sort)->toBe([['key' => 'name', 'direction' => 'desc']]);
        }
        $calls = $handler::$calls;
        foreach ([['limit' => 101], ['filters' => '{'], ['filters' => '[]'], ['filters' => '{"hidden":"probe"}'],
            ['kind' => 'record', 'field' => 'name'], ['selection' => 'probe'], ['public_id' => '1']] as $query) {
            $this->getJson($path.'?'.http_build_query($query))->assertUnprocessable()->assertJsonMissingPath('data');
        }
        $this->getJson($path.'?kind=options&field=reference')->assertNotFound()->assertJsonMissingPath('data');
        expect($handler::$calls)->toBe($calls);
        $this->getJson($path.'?kind=options&field=allowed_reference&limit=1')->assertOk()->assertJsonPath('data.field', 'allowed_reference');
        $handler::$rawSecret = true;
        $this->getJson($path)->assertServiceUnavailable()->assertJsonMissingPath('data');
        expect(route('runa.admin.section.read', ['owner' => 'app', 'page' => 'users', 'section' => 'profile'], false))->toBe($path);
        expect(fn () => $registrations->register(new $provider))->toThrow(InvalidArgumentException::class);
        $this->getJson('/api/admin')->assertOk()->assertJsonPath('data.owners.0.id', 'app');
    } finally {
        $sources->remove();
    }
});

it('dispatches independent Admin commands outside the entry root with bounded review input and safe failures', function (): void {
    $path = '/api/admin/app/users/sections/profile';
    $this->postJson($path.'/commands', [])->assertUnauthorized()->assertJsonMissingPath('errors');
    ($this->adminGrant)('runa.admin.enter');
    ($this->adminGrant)('runa.auth.profiles.manage');
    ($this->adminLogin)();
    $this->postJson($path.'/commands', [])->assertForbidden()->assertJsonPath('scope', 'entry')->assertJsonMissingPath('errors');
    ($this->adminProof)();
    $sources = new ModuleSources;
    $namespace = 'CommandFixture'.bin2hex(random_bytes(6));
    $directory = $sources->root.'/app/Management/'.$namespace;
    mkdir($directory, 0775, true);
    foreach (['Provider', 'Handler'] as $name) {
        $file = $directory.'/'.$name.'.php';
        file_put_contents($file, str_replace(['FIXTURE_NAMESPACE', 'runa.auth.profile.update'], [$namespace, 'runa.auth.profiles.manage'], file_get_contents(__DIR__.'/../../Fixtures/Management/Admin'.$name.'.php.stub')));
        require $file;
    }
    $provider = 'App\\Management\\'.$namespace.'\\Provider';
    $handler = 'App\\Management\\'.$namespace.'\\Handler';
    app()->instance(ApplicationManagement::class, new ApplicationManagement(app(), $sources->root));
    app()->forgetInstance(ManagementPages::class);
    app()->bind($handler, $handler);
    app()->afterResolving(AdminRegistry::class, fn ($registry) => $registry->register(new $provider));
    $handler::$writing = true;
    try {
        $this->getJson('/api/admin/app/users')->assertOk()->assertJsonPath('data.sections.0.commands.0.inputs', ['name'])
            ->assertJsonPath('data.sections.0.commands.0.intent', 'save');
        $input = ['command' => 'update', 'public_id' => null, 'revision' => 'revision-1', 'values' => ['name' => 'After']];
        foreach (['hidden', 'token', 'foreign'] as $field) {
            $this->postJson($path.'/commands', array_replace($input, ['values' => [$field => 'unsafe-input-marker']]))->assertUnprocessable();
        }
        expect($handler::$writes)->toBe(0);
        $this->postJson($path.'/commands', array_replace($input, ['values' => ['name' => 'Invalid']]))->assertUnprocessable()
            ->assertJsonPath('errors.name', ['Invalid input.'])->assertJsonMissingPath('errors.hidden')->assertDontSee('private-validation-marker');
        $provider::$previewRequired = true;
        $provider::$destructive = true;
        $this->postJson($path.'/preview', $input)->assertOk()->assertJsonPath('data.changes.name', 'After');
        $this->postJson($path.'/commands', $input)->assertUnprocessable();
        $confirmed = $input + ['confirmed' => true];
        $this->postJson($path.'/commands', $confirmed)->assertConflict();
        $review = $this->postJson($path.'/preview', $input)->assertOk()->json('data');
        $this->postJson($path.'/commands', $confirmed + ['review' => $review])->assertOk()
            ->assertJsonPath('data.status', 'committed')->assertJsonPath('data.record.values.name', 'After')
            ->assertJsonMissingPath('data.record.values.hidden')->assertJsonMissingPath('data.record.values.unlisted');
        expect($handler::$writes)->toBe(1)->and($handler::$writeTransactionLevel)->toBe(0);
        $this->postJson($path.'/commands', $confirmed + ['review' => $review])->assertConflict();
        expect($handler::$writes)->toBe(1);
        expect(route('runa.admin.command', ['owner' => 'app', 'page' => 'users', 'section' => 'profile'], false))->toBe($path.'/commands');
        $this->postJson('/api/admin/app/users/save', ['revision' => 'page', 'participants' => [['id' => 'profile', 'input' => $input]]])
            ->assertConflict()->assertJsonMissingPath('data');
    } finally {
        $sources->remove();
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

it('advertises only current entry availability without requiring or consuming password proof', function (): void {
    $this->getJson('/api/admin/entry')->assertUnauthorized()->assertJsonMissingPath('data');
    ($this->adminLogin)();
    $this->getJson('/api/admin/entry')->assertOk()->assertExactJson(['data' => ['available' => false]])
        ->assertHeader('Cache-Control', 'no-store, private');
    ($this->adminGrant)('runa.admin.enter');
    $this->getJson('/api/admin/entry')->assertOk()->assertExactJson(['data' => ['available' => true]]);
    $this->getJson('/api/admin')->assertForbidden()->assertJsonPath('reason', 'step_up_required');
    expect(DB::table('runa_auth.step_up_proofs')->count())->toBe(0);
    DB::table('runa_permissions.user_permissions')->where('user_public_id', $this->adminUser->public_id)->delete();
    $this->getJson('/api/admin/entry')->assertOk()->assertExactJson(['data' => ['available' => false]]);
    ($this->adminGrant)('runa.admin.enter');
    DB::table('shared.modules')->where('module_id', 'runa.admin')->update(['installed_version' => null]);
    DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    // Missing mandatory Core activates ordinary platform maintenance before route admission.
    $this->getJson('/api/admin/entry')->assertServiceUnavailable()->assertJsonMissingPath('data');
});

it('routes Admin pages through guarded reads and never relaxes maintenance for advertisement', function (): void {
    $this->getJson('/api/admin/app/users/sections/profile?limit=invalid')->assertUnauthorized()->assertJsonMissingPath('errors');
    ($this->adminGrant)('runa.admin.enter');
    ($this->adminLogin)();
    $this->getJson('/api/admin/app/users')->assertForbidden()->assertJsonPath('reason', 'step_up_required');
    $this->getJson('/api/admin/app/users/sections/profile?limit=invalid')->assertForbidden()->assertJsonPath('reason', 'step_up_required')->assertJsonMissingPath('errors');
    ($this->adminProof)();
    $this->getJson('/api/admin/app/users')->assertNotFound()->assertJsonMissingPath('data');
    expect(route('runa.admin.dashboard', absolute: false))->toBe('/admin')
        ->and(route('runa.admin.page', ['owner' => 'app', 'page' => 'users'], false))->toBe('/admin/app/users');
    $marker = app()->storagePath('framework/maintenance/enabled');
    if (file_exists($marker)) {
        throw new RuntimeException('Admin fixture refuses an existing maintenance marker.');
    }
    if (! is_dir(dirname($marker))) {
        mkdir(dirname($marker), 0775, true);
    }
    file_put_contents($marker, 'admin-advertisement-fixture');
    try {
        $this->getJson('/api/admin/entry')->assertServiceUnavailable()->assertJsonMissingPath('data');
    } finally {
        unlink($marker);
    }
});

/** Real HTTP fixture: each owner supplies its own current resource revision. */
function adminAggregateInput(object $test, string $publicId, array $permissions = []): array
{
    $path = '/api/admin/runa.auth/users';
    $page = $test->getJson($path)->assertOk()->json('data');
    $profile = $test->getJson($path.'/sections/profile?public_id='.$publicId)->assertOk()->json('data');
    $id = 'runa.permissions.user_access.assignments';
    $access = $test->getJson($path.'/sections/'.$id.'?public_id='.$publicId)->assertOk()->json('data');

    return ['revision' => $page['revision'], 'participants' => [
        ['id' => $id, 'input' => ['command' => 'save', 'public_id' => $publicId, 'revision' => $access['revision'], 'values' => ['roles' => [], 'permissions' => $permissions]]],
        ['id' => 'profile', 'input' => ['command' => 'save', 'public_id' => $publicId, 'revision' => $profile['revision'], 'values' => array_replace($profile['values'], ['name' => 'After'])]],
    ]];
}

function adminAggregateReview(object $test, array $input): array
{
    $review = $test->postJson('/api/admin/runa.auth/users/preview', $input)->assertOk()->json('data');
    $input['revision'] = $review['revision'];
    foreach ($input['participants'] as &$part) {
        $part['input']['review'] = array_values(array_filter($review['participants'], fn ($item) => $item['id'] === $part['id']))[0]['review'];
    }

    return $input;
}

function adminAggregateLogin(object $test): void
{
    foreach (['runa.admin.enter', 'runa.auth.identities.read', 'runa.auth.profiles.manage', 'runa.auth.eligibility.read',
        'runa.permissions.introspection.read', 'runa.permissions.access.manage'] as $code) {
        ($test->adminGrant)($code);
    }
    nativeMutationProof($test);
    ($test->adminProof)();
}

it('coordinates an Admin aggregate HTTP review and Auth first atomic Save with safe owner projections', function (): void {
    adminAggregateLogin($this);
    $target = app(ChangeIdentity::class)->create(new NewIdentity('aggregate@example.test', name: 'Before', active: true, approved: true));
    $permission = DB::table('runa_permissions.permissions')->where('code', 'runa.audit.read')->value('public_id');
    $input = adminAggregateInput($this, $target->publicId, [$permission]);
    $this->postJson('/api/admin/runa.auth/users/save', $input)->assertConflict();
    $input = adminAggregateReview($this, $input);
    $this->postJson('/api/admin/runa.auth/users/save', $input)->assertOk()
        ->assertJsonPath('data.status', 'committed')->assertJsonPath('data.participants.0.id', 'profile')
        ->assertJsonPath('data.participants.0.record.values.name', 'After')
        ->assertJsonPath('data.participants.1.record.values.permissions', [$permission]);
    expect(User::where('public_id', $target->publicId)->value('name'))->toBe('After')
        ->and(DB::table('runa_audit.events')->where('action', 'profile.admin_changed')->count())->toBe(1)
        ->and(DB::table('runa_audit.events')->where('action', 'assignments.changed')->count())->toBe(1)
        ->and(DB::table('runa_auth.step_up_proofs')->where('action', 'runa.permissions.access.manage')->value('consumed_at'))->not->toBeNull()
        ->and(DB::table('runa_auth.step_up_proofs')->where('action', 'runa.admin.enter')->value('consumed_at'))->toBeNull();
    $this->postJson('/api/admin/runa.auth/users/save', $input)->assertConflict();
    $fresh = adminAggregateReview($this, adminAggregateInput($this, $target->publicId, [$permission]));
    $this->postJson('/api/admin/runa.auth/users/save', $fresh)->assertForbidden();
});

it('rolls back an Admin aggregate in either owning Audit failure direction including consumed action proof', function (string $action): void {
    adminAggregateLogin($this);
    $target = app(ChangeIdentity::class)->create(new NewIdentity('aggregate-rollback@example.test', name: 'Before', active: true, approved: true));
    $permission = DB::table('runa_permissions.permissions')->where('code', 'runa.audit.read')->value('public_id');
    $input = adminAggregateReview($this, adminAggregateInput($this, $target->publicId, [$permission]));
    // The action comes from this fixed test dataset, never from request input.
    DB::statement("ALTER TABLE runa_audit.events ADD CONSTRAINT aggregate_audit_failure CHECK (action <> '".$action."') NOT VALID");
    try {
        $this->postJson('/api/admin/runa.auth/users/save', $input)->assertServiceUnavailable()->assertJsonMissingPath('data');
    } finally {
        DB::statement('ALTER TABLE runa_audit.events DROP CONSTRAINT aggregate_audit_failure');
    }
    expect(User::where('public_id', $target->publicId)->value('name'))->toBe('Before')
        ->and(User::where('public_id', $target->publicId)->value('version'))->toBe($target->version)
        ->and(DB::table('runa_permissions.user_access')->where('user_public_id', $target->publicId)->count())->toBe(0)
        ->and(DB::table('runa_auth.step_up_proofs')->where('action', 'runa.permissions.access.manage')->value('consumed_at'))->toBeNull()
        ->and(DB::table('runa_audit.events')->where('action', 'access.refused')->count())->toBe(1);
    // The exact reviewed intent can be retried explicitly after rollback; no automatic replay occurred.
    $this->postJson('/api/admin/runa.auth/users/save', $input)->assertOk();
})->with(['profile.admin_changed', 'assignments.changed']);

function adminAtomicApp(object $test, string $publicId): array
{
    if (DB::selectOne("SELECT 1 FROM pg_namespace WHERE nspname = 'admin_fixture'")) {
        throw new RuntimeException('App aggregate fixture requires its own absent disposable schema.');
    }
    DB::statement('CREATE SCHEMA admin_fixture');
    DB::statement('CREATE TABLE admin_fixture.notes (id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY, public_id uuid NOT NULL UNIQUE, note text NOT NULL, version integer NOT NULL)');
    DB::table('admin_fixture.notes')->insert(['public_id' => $publicId, 'note' => 'Before', 'version' => 1]);
    $namespace = 'AtomicFixture'.bin2hex(random_bytes(6));
    $directory = base_path('app/Management/'.$namespace);
    mkdir($directory, 0775, true);
    $test->adminAtomicDirectory = $directory;
    foreach (['AtomicMutation', 'AtomicEditor', 'AtomicProvider'] as $name) {
        $file = $directory.'/'.$name.'.php';
        file_put_contents($file, str_replace('FIXTURE_NAMESPACE', $namespace, file_get_contents(__DIR__.'/../../Fixtures/Management/'.$name.'.php.stub')));
        require $file;
    }
    $prefix = 'App\\Management\\'.$namespace.'\\';
    app()->bind($prefix.'AtomicEditor', $prefix.'AtomicEditor');
    app()->afterResolving(AdminRegistry::class, fn ($registry) => $registry->register(new ($prefix.'AtomicProvider')));

    return [$prefix.'AtomicProvider', $prefix.'AtomicEditor', $prefix.'AtomicMutation'];
}

function adminAtomicAppInput(array $input, string $publicId, string $note = 'After'): array
{
    $input['participants'][] = ['id' => 'app.notes.note', 'input' => ['command' => 'save', 'public_id' => $publicId, 'revision' => '1', 'values' => ['note' => $note]]];

    return $input;
}

it('includes an App editable contribution and rolls back invalid final projection before commit', function (bool $badProjection): void {
    adminAggregateLogin($this);
    $target = app(ChangeIdentity::class)->create(new NewIdentity('aggregate-app@example.test', name: 'Before', active: true, approved: true));
    [, $editor] = adminAtomicApp($this, $target->publicId);
    $input = adminAtomicAppInput(adminAggregateInput($this, $target->publicId), $target->publicId);
    $input = adminAggregateReview($this, $input);
    $editor::$badProjection = $badProjection;
    $response = $this->postJson('/api/admin/runa.auth/users/save', $input);
    if ($badProjection) {
        $response->assertConflict()->assertJsonMissingPath('data');
    } else {
        $response->assertOk()->assertJsonPath('data.participants.0.id', 'app.notes.note')->assertJsonPath('data.participants.0.record.values.note', 'After');
    }
    expect(DB::table('admin_fixture.notes')->value('note'))->toBe($badProjection ? 'Before' : 'After')
        ->and(User::where('public_id', $target->publicId)->value('name'))->toBe($badProjection ? 'Before' : 'After')
        ->and(DB::table('runa_audit.events')->where('action', 'note.changed')->count())->toBe($badProjection ? 0 : 1)
        ->and(DB::table('runa_auth.step_up_proofs')->where('action', 'runa.permissions.access.manage')->value('consumed_at') === null)->toBe($badProjection);
})->with([false, true]);

it('rejects missing duplicate incompatible and lost aggregate participants and namespaces owning validation errors', function (): void {
    adminAggregateLogin($this);
    $target = app(ChangeIdentity::class)->create(new NewIdentity('aggregate-loss@example.test', name: 'Before', active: true, approved: true));
    [$provider, , $mutation] = adminAtomicApp($this, $target->publicId);
    $input = adminAtomicAppInput(adminAggregateInput($this, $target->publicId), $target->publicId);
    $missing = $input;
    array_pop($missing['participants']);
    $this->postJson('/api/admin/runa.auth/users/preview', $missing)->assertConflict();
    $invalid = $input;
    $invalid['participants'][2]['input']['values']['note'] = 'Invalid';
    $this->postJson('/api/admin/runa.auth/users/preview', $invalid)->assertUnprocessable()
        ->assertJsonValidationErrors(['app.notes.note.note'])->assertJsonMissingPath('errors.hidden')->assertDontSee('private-marker');
    $duplicate = $input;
    $duplicate['participants'][] = $input['participants'][0];
    $this->postJson('/api/admin/runa.auth/users/preview', $duplicate)->assertUnprocessable();
    $reviewed = adminAggregateReview($this, $input);
    $mutation::$duplicateProof = true;
    $this->postJson('/api/admin/runa.auth/users/save', $reviewed)->assertConflict();
    $mutation::$duplicateProof = false;
    $provider::$present = false;
    $this->postJson('/api/admin/runa.auth/users/save', $reviewed)->assertConflict();
    $provider::$present = true;
    $mutation::$probe = static function () use ($provider): void {
        $provider::$present = false;
    };
    $this->postJson('/api/admin/runa.auth/users/save', $reviewed)->assertConflict();
    $mutation::$probe = null;
    expect(DB::table('admin_fixture.notes')->value('note'))->toBe('Before')
        ->and(User::where('public_id', $target->publicId)->value('name'))->toBe('Before')
        ->and(DB::table('runa_auth.step_up_proofs')->where('action', 'runa.permissions.access.manage')->value('consumed_at'))->toBeNull();
});

it('rejects aggregate ABA and expired owning proof without regenerating reviewed intent', function (): void {
    adminAggregateLogin($this);
    $target = app(ChangeIdentity::class)->create(new NewIdentity('aggregate-aba@example.test', name: 'Before', active: true, approved: true));
    $input = adminAggregateReview($this, adminAggregateInput($this, $target->publicId));
    app(PrincipalScope::class)->run(new TrustedPrincipal($this->adminUser->public_id), function () use ($target): void {
        $profiles = app(ModuleApis::class)->resolve('runa.auth', AuthProfiles::class, '2.0.0');
        $changed = $profiles->edit($target->publicId, $target->version, new ProfileChange(name: 'Intervening'));
        $profiles->edit($target->publicId, $changed->version, new ProfileChange(name: 'Before'));
    });
    $this->postJson('/api/admin/runa.auth/users/save', $input)->assertConflict();
    $current = adminAggregateReview($this, adminAggregateInput($this, $target->publicId));
    DB::table('runa_auth.step_up_proofs')->where('action', 'runa.permissions.access.manage')->update(['expires_at' => now('UTC')->subSecond()]);
    $this->postJson('/api/admin/runa.auth/users/save', $current)->assertForbidden();
    expect(User::where('public_id', $target->publicId)->value('name'))->toBe('Before')
        ->and(DB::table('runa_audit.events')->where('action', 'assignments.changed')->count())->toBe(0);
});

it('rolls back own grant removal when current result authority is lost', function (): void {
    adminAggregateLogin($this);
    $input = adminAggregateReview($this, adminAggregateInput($this, $this->adminUser->public_id));
    $this->postJson('/api/admin/runa.auth/users/save', $input)->assertForbidden()->assertJsonMissingPath('data');
    expect(User::where('public_id', $this->adminUser->public_id)->value('name'))->toBe('Admin entry')
        ->and(DB::table('runa_permissions.user_permissions')->where('user_public_id', $this->adminUser->public_id)->count())->toBe(6)
        ->and(DB::table('runa_auth.step_up_proofs')->where('action', 'runa.permissions.access.manage')->value('consumed_at'))->toBeNull();
});

it('holds aggregate registry and complete sorted subject locks against an independent PostgreSQL competitor', function (): void {
    adminAggregateLogin($this);
    $target = app(ChangeIdentity::class)->create(new NewIdentity('aggregate-lock@example.test', name: 'Before', active: true, approved: true));
    $other = app(ChangeIdentity::class)->create(new NewIdentity('aggregate-subject@example.test', name: 'Other', active: true, approved: true));
    [, , $mutation] = adminAtomicApp($this, $other->publicId);
    $input = adminAtomicAppInput(adminAggregateInput($this, $target->publicId), $other->publicId);
    $input = adminAggregateReview($this, $input);
    $competition = new AggregateCompetition;
    $observed = [];
    DB::connection()->enableQueryLog();
    $mutation::$probe = function () use ($competition, $target, $other, &$observed): void {
        $ids = [$this->adminUser->public_id, $target->publicId, $other->publicId];
        sort($ids);
        foreach (DB::getQueryLog() as $query) {
            if (str_contains($query['query'], '"runa_auth"."users"') && str_contains($query['query'], 'for update') && in_array($query['bindings'][0] ?? null, $ids, true)) {
                $observed[] = $query['bindings'][0];
            }
        }
        $competition->blocked(fn ($connection) => $connection->table('shared.module_registry_state')->where('id', 1)->increment('generation'));
        foreach ($ids as $id) {
            $competition->blocked(fn ($connection) => $connection->table('runa_auth.users')->where('public_id', $id)->increment('version'));
        }
        expect(array_slice($observed, -3))->toBe($ids);
    };
    try {
        $this->postJson('/api/admin/runa.auth/users/save', $input)->assertOk();
        expect($observed)->not->toBeEmpty();
    } finally {
        $mutation::$probe = null;
        DB::connection()->disableQueryLog();
        $competition->close();
    }
});

it('fails closed when an Admin aggregate rollback refusal Audit is unavailable', function (): void {
    adminAggregateLogin($this);
    $target = app(ChangeIdentity::class)->create(new NewIdentity('aggregate-refusal@example.test', name: 'Before', active: true, approved: true));
    $permission = DB::table('runa_permissions.permissions')->where('code', 'runa.audit.read')->value('public_id');
    $input = adminAggregateReview($this, adminAggregateInput($this, $target->publicId, [$permission]));
    DB::statement("ALTER TABLE runa_audit.events ADD CONSTRAINT aggregate_refusal_failure CHECK (action NOT IN ('assignments.changed', 'access.refused')) NOT VALID");
    try {
        $this->postJson('/api/admin/runa.auth/users/save', $input)->assertServiceUnavailable()->assertJsonPath('reason', 'transaction_refusal_unavailable');
        expect(User::where('public_id', $target->publicId)->value('name'))->toBe('Before')
            ->and(DB::table('runa_permissions.user_access')->where('user_public_id', $target->publicId)->count())->toBe(0)
            ->and(DB::table('runa_auth.step_up_proofs')->where('action', 'runa.permissions.access.manage')->value('consumed_at'))->toBeNull();
    } finally {
        DB::statement('ALTER TABLE runa_audit.events DROP CONSTRAINT aggregate_refusal_failure');
    }
});
