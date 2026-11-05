<?php

use App\Settings\PointTenFixture\Settings;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Http\Request;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Runa\Contracts\Modules\ManagementRead;
use Runa\Contracts\Modules\ManagementWrite;
use Runa\Contracts\Modules\Preparation;
use Runa\Contracts\Modules\PreparationParticipant;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\Http\HttpErrorPage;
use Runa\Identity\ApplicationIdentity;
use Runa\Integration\Core\AdminDiagnostics;
use Runa\Integration\Core\AdminHttpGuard;
use Runa\Integration\Core\AdminOperationalAuthority;
use Runa\Mail\MailBranding;
use Runa\ModuleFramework\Api\ModuleDeclarations;
use Runa\ModuleFramework\Api\ModuleHttp;
use Runa\ModuleFramework\Api\OperationGuard;
use Runa\ModuleFramework\Api\PrincipalScope;
use Runa\ModuleFramework\LifecyclePolicies;
use Runa\ModuleFramework\Management\ApplicationManagement;
use Runa\ModuleFramework\Management\ManagementPages;
use Runa\ModuleFramework\ModuleAvailability;
use Runa\ModuleFramework\ModuleHealth;
use Runa\ModuleFramework\ModuleOperationHistory;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\ModuleFramework\Updates\ModuleUpdates;
use Runa\Modules\Admin\Contracts\AdminAccess;
use Runa\Modules\Admin\Contracts\AdminRegistry;
use Runa\Modules\Audit\Contracts\AuditEntity;
use Runa\Modules\Audit\Contracts\AuditEvent;
use Runa\Modules\Audit\Contracts\AuditRecords;
use Runa\Modules\Audit\Contracts\AuditUnavailable;
use Runa\Modules\Auth\Actions\ChangeIdentity;
use Runa\Modules\Auth\Actions\EntryTokens;
use Runa\Modules\Auth\Actions\ManageProfiles;
use Runa\Modules\Auth\Actions\ProfileMutation;
use Runa\Modules\Auth\Actions\Profiles;
use Runa\Modules\Auth\Actions\SessionStore;
use Runa\Modules\Auth\Contracts\AuthAdministration;
use Runa\Modules\Auth\Contracts\AuthLifecycle;
use Runa\Modules\Auth\Contracts\AuthProfiles;
use Runa\Modules\Auth\Contracts\LifecycleChange;
use Runa\Modules\Auth\Contracts\NewIdentity;
use Runa\Modules\Auth\Contracts\ProfileChange;
use Runa\Modules\Auth\Contracts\UserDirectoryQuery;
use Runa\Modules\Auth\Definitions\IdentitySettings;
use Runa\Modules\Auth\Management\AdministrationPages;
use Runa\Modules\Auth\Management\ProfileEditor;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Support\BulkUserOperations;
use Runa\Modules\Permissions\Actions\ChangeAssignments;
use Runa\Modules\Permissions\Contracts\AssignmentSet;
use Runa\Modules\Permissions\Contracts\PermissionsAccess;
use Runa\Runtime\MaintenanceState;
use Runa\Settings\SettingRegistry;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Data\ModuleTransactions;
use Runa\Support\Modules\Development\ModuleContractSuite;
use Runa\Support\Modules\Settings\ModuleSettings;
use Runa\Support\Modules\Storage\ModuleStorage;
use Runa\Support\Modules\Work\ModuleWork;
use Symfony\Component\Mime\Email;
use Tests\Fixtures\AggregateCompetition;
use Tests\Fixtures\CoreModuleStorage;
use Tests\Fixtures\ModuleSources;
use Tests\Fixtures\OperationalModule;
use Tests\Fixtures\TransitionPolicy;
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
    $identity = app(ChangeIdentity::class)->create(new NewIdentity('admin-entry@example.test', name: 'Admin entry', active: true));
    $this->adminUser = User::where('public_id', $identity->publicId)->firstOrFail();
    DB::transaction(function (): void {
        $this->adminUser->password = Hash::make('admin-entry-password');
        $this->adminUser->password_changed_at = now();
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
        $this->postJson('/api/runa/auth/step-up', ['action' => $action, 'current_password' => 'admin-entry-password'])->assertOk();
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
    DB::table('shared.module_settings_snapshots')->where('module_id', 'runa.auth')->delete();
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
    $test->postJson('/api/runa/auth/step-up', ['action' => 'runa.permissions.access.manage', 'current_password' => 'admin-entry-password'])->assertOk();
}

it('validates both owners before Auth first apply and commits profile assignments proof and Audit together', function (): void {
    foreach (['runa.auth.profiles.manage', 'runa.auth.eligibility.read', 'runa.permissions.access.manage'] as $code) {
        ($this->adminGrant)($code);
    }
    $target = app(ChangeIdentity::class)->create(new NewIdentity('native-mutation@example.test', name: 'Before', active: true));
    nativeMutationProof($this);
    app(PrincipalScope::class)->run(new TrustedPrincipal($this->adminUser->public_id), function () use ($target): void {
        $api = app(ModuleApis::class)->resolve('runa.permissions', PermissionsAccess::class, '2.2.0');
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
    $target = app(ChangeIdentity::class)->create(new NewIdentity('native-rollback@example.test', name: 'Before', active: true));
    nativeMutationProof($this);
    app(PrincipalScope::class)->run(new TrustedPrincipal($this->adminUser->public_id), function () use ($target): void {
        $api = app(ModuleApis::class)->resolve('runa.permissions', PermissionsAccess::class, '2.2.0');
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
    $target = app(ChangeIdentity::class)->create(new NewIdentity('native-stale@example.test', name: 'Before', active: true));
    $other = app(ChangeIdentity::class)->create(new NewIdentity('native-other@example.test', name: 'Other', active: true));
    nativeMutationProof($this);
    app(PrincipalScope::class)->run(new TrustedPrincipal($this->adminUser->public_id), function () use ($target, $other): void {
        $apis = app(ModuleApis::class);
        expect(fn () => $apis->resolve('runa.auth', AuthProfiles::class, '1.0.0'))->toThrow(OperationRejected::class);
        $api = $apis->resolve('runa.permissions', PermissionsAccess::class, '2.2.0');
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
        $updated = $apis->resolve('runa.auth', AuthProfiles::class, '2.1.0')->edit($target->publicId, $target->version, new ProfileChange(name: 'Intervening'));
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
    $target = app(ChangeIdentity::class)->create(new NewIdentity('native-refusal@example.test', name: 'Before', active: true));
    nativeMutationProof($this);
    app(PrincipalScope::class)->run(new TrustedPrincipal($this->adminUser->public_id), function () use ($target): void {
        $api = app(ModuleApis::class)->resolve('runa.permissions', PermissionsAccess::class, '2.2.0');
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
        ['id' => 'profile', 'input' => ['command' => 'save', 'public_id' => $publicId, 'revision' => $profile['revision'], 'values' => array_replace(array_intersect_key($profile['values'], array_flip(['name', 'first_name', 'surname', 'login'])), ['name' => 'After'])]],
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
    CarbonImmutable::setTestNow('2026-10-31 12:00:00 UTC');
    adminAggregateLogin($this);
    CarbonImmutable::setTestNow('2026-10-31 12:04:00 UTC');
    $this->postJson('/api/admin/activity')->assertOk();
    CarbonImmutable::setTestNow('2026-10-31 12:05:01 UTC');
    // Native aggregate admission accepts idle access beyond the original fixed proof.
    // Its owning sensitive operation still needs an independently fresh proof.
    $this->postJson('/api/runa/auth/step-up', ['action' => 'runa.permissions.access.manage', 'current_password' => 'admin-entry-password'])->assertOk();
    $target = app(ChangeIdentity::class)->create(new NewIdentity('aggregate@example.test', name: 'Before', active: true));
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
    $target = app(ChangeIdentity::class)->create(new NewIdentity('aggregate-rollback@example.test', name: 'Before', active: true));
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
    $target = app(ChangeIdentity::class)->create(new NewIdentity('aggregate-app@example.test', name: 'Before', active: true));
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
    $target = app(ChangeIdentity::class)->create(new NewIdentity('aggregate-loss@example.test', name: 'Before', active: true));
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
    $target = app(ChangeIdentity::class)->create(new NewIdentity('aggregate-aba@example.test', name: 'Before', active: true));
    $input = adminAggregateReview($this, adminAggregateInput($this, $target->publicId));
    app(PrincipalScope::class)->run(new TrustedPrincipal($this->adminUser->public_id), function () use ($target): void {
        $profiles = app(ModuleApis::class)->resolve('runa.auth', AuthProfiles::class, '2.1.0');
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
    $target = app(ChangeIdentity::class)->create(new NewIdentity('aggregate-lock@example.test', name: 'Before', active: true));
    $other = app(ChangeIdentity::class)->create(new NewIdentity('aggregate-subject@example.test', name: 'Other', active: true));
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
    $target = app(ChangeIdentity::class)->create(new NewIdentity('aggregate-refusal@example.test', name: 'Before', active: true));
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

function authAdministrationLogin(object $test, array $grants): void
{
    foreach (['runa.admin.enter', 'runa.auth.identities.read', ...$grants] as $grant) {
        ($test->adminGrant)($grant);
    }
    ($test->adminLogin)();
    ($test->adminProof)();
}

function authAdministrationRead(object $test, string $section, ?string $publicId = null, array $query = []): array
{
    return $test->getJson('/api/admin/runa.auth/users/sections/'.$section.'?'.http_build_query([
        ...($publicId === null ? [] : ['public_id' => $publicId]), ...$query,
    ]))->assertOk()->json('data');
}

it('Auth administration directory bounds cursors searches and private profile disclosure', function (): void {
    $target = app(ChangeIdentity::class)->create(new NewIdentity('private-directory@example.test', name: 'Directory target', login: 'private_login'));
    authAdministrationLogin($this, ['runa.auth.users.read']);
    $directory = authAdministrationRead($this, 'directory', query: ['kind' => 'page', 'limit' => 1]);
    expect($directory['items'])->toHaveCount(1)->and($directory['total'])->toBe(2)->and($directory['next'])->toBeString();
    $next = authAdministrationRead($this, 'directory', query: ['kind' => 'page', 'limit' => 1, 'cursor' => $directory['next']]);
    expect($next['items'][0]['public_id'])->toBe($target->publicId)->and($next['next'])->toBeNull()
        ->and(json_encode($directory))->not->toContain('private-directory@example.test', 'private_login', 'password', 'security_version');
    $empty = authAdministrationRead($this, 'directory', query: ['kind' => 'page', 'filters' => json_encode(['display_name' => 'private_login'])]);
    expect($empty['items'])->toBe([]);
    foreach ([['state' => 'deleted'], ['state' => 'all']] as $filter) {
        $this->getJson('/api/admin/runa.auth/users/sections/directory?'.http_build_query(['kind' => 'page', 'filters' => json_encode($filter)]))->assertForbidden();
    }
    $this->getJson('/api/admin/runa.auth/users/sections/directory?kind=page&cursor=malformed')->assertUnprocessable();
    $this->getJson('/api/admin/runa.auth/users/sections/directory?'.http_build_query(['kind' => 'page', 'cursor' => $directory['next'], 'filters' => json_encode(['display_name' => 'different'])]))->assertUnprocessable();
    ($this->adminGrant)('runa.auth.profiles.read');
    $found = authAdministrationRead($this, 'directory', query: ['kind' => 'page', 'filters' => json_encode(['display_name' => 'private_login'])]);
    expect($found['items'])->toHaveCount(1);
    $email = authAdministrationRead($this, 'primary_email', $target->publicId);
    expect($email['values'])->not->toHaveKey('current_email')->not->toHaveKey('verified')->not->toHaveKey('verification_required')
        ->and(json_encode($email))->not->toContain('private-directory@example.test');
    ($this->adminGrant)('runa.auth.emails.read');
    expect(authAdministrationRead($this, 'primary_email', $target->publicId)['values']['current_email'])->toBe('private-directory@example.test');
    ($this->adminGrant)('runa.auth.users.manage');
    app(PrincipalScope::class)->run(new TrustedPrincipal($this->adminUser->public_id), fn () => app(ModuleApis::class)->resolve('runa.auth', AuthLifecycle::class, '2.1.0')->change($target->publicId, 1, new LifecycleChange('delete')));
    $this->getJson('/api/admin/runa.auth/users/sections/primary_email?public_id='.$target->publicId)->assertNotFound();
    ($this->adminGrant)('runa.auth.users.deleted.read');
    $deleted = authAdministrationRead($this, 'directory', query: ['kind' => 'page', 'filters' => json_encode(['state' => 'deleted'])]);
    expect($deleted['items'][0]['values']['state'])->toBe('deleted')->and($deleted['items'][0]['public_id'])->toBe($target->publicId);
    expect(authAdministrationRead($this, 'lifecycle', $target->publicId)['values']['deleted'])->toBeTrue();
});

it('Auth administration dispatches every lifecycle intent with native revisions and Audit', function (): void {
    DB::transaction(function (): void {
        $this->adminUser->password_changed_at = now();
        $this->adminUser->save();
    });
    $settings = app(ModuleSettings::class)->read('runa.auth');
    app(ModuleSettings::class)->update('runa.auth', ['registration_activation' => 'administrator', 'password_expiry_days' => 30], $settings['revision'], $settings['generation']);
    $target = app(ChangeIdentity::class)->create(new NewIdentity('lifecycle-admin@example.test', name: 'Lifecycle target'));
    authAdministrationLogin($this, ['runa.auth.users.read', 'runa.auth.users.deleted.read', 'runa.auth.users.manage']);
    foreach (['activate', 'schedule_activation', 'schedule_deactivation', 'set_attempt_lock', 'set_security_lock', 'require_password_change', 'deactivate', 'delete', 'restore'] as $operation) {
        $record = authAdministrationRead($this, 'lifecycle', $target->publicId);
        $input = ['command' => 'change', 'public_id' => $target->publicId, 'revision' => $record['revision'],
            'confirmed' => true, 'values' => ['operation' => $operation, 'at' => now()->utc()->addDays($operation === 'schedule_activation' ? 1 : 2)->format('Y-m-d\TH:i:s.000\Z'), 'enabled' => true]];
        $this->postJson('/api/admin/runa.auth/users/sections/lifecycle/commands', $input)->assertOk()->assertJsonPath('data.status', 'committed');
        if ($operation === 'activate') {
            $this->postJson('/api/admin/runa.auth/users/sections/lifecycle/commands', $input)->assertConflict();
        }
    }
    $user = User::where('public_id', $target->publicId)->firstOrFail();
    expect($user->active)->toBeFalse()->and($user->security_locked)->toBeTrue()->and($user->password_change_required)->toBeTrue()
        ->and(DB::table('runa_audit.entities')->where('entity_public_id', $target->publicId)->exists())->toBeTrue();
});

it('Auth administration keeps creations invitations secondary addresses and preference resets in owning protocols', function (): void {
    authAdministrationLogin($this, ['runa.auth.users.create', 'runa.auth.profiles.read', 'runa.auth.secondary.read', 'runa.auth.secondary.manage', 'runa.auth.invitations.manage', 'runa.auth.preferences.reset']);
    ($this->adminProof)('runa.auth.users.create');
    $this->postJson('/api/admin/runa.auth/users/sections/creation/commands', ['command' => 'create', 'revision' => '0', 'public_id' => null,
        'values' => authCreationValues(['email' => 'created-admin@example.test', 'name' => 'Created account', 'active' => true])])->assertOk();
    $user = User::where('email', 'created-admin@example.test')->firstOrFail();
    $invite = ['command' => 'invite', 'public_id' => $user->public_id, 'revision' => (string) $user->version, 'values' => []];
    foreach ([1, 2] as $attempt) {
        $response = $this->postJson('/api/admin/runa.auth/users/sections/invitations/commands', $invite)->assertOk();
        expect($response->getContent())->not->toContain('delivery_secret', 'token_hash', 'password');
    }
    expect(DB::table('runa_auth.entry_tokens')->where('user_id', $user->id)->whereNull('invalidated_at')->count())->toBe(1);
    $invite['command'] = 'invalidate';
    $invite['confirmed'] = true;
    $this->postJson('/api/admin/runa.auth/users/sections/invitations/commands', $invite)->assertOk();
    // Secondary addresses retain native enablement; Admin cannot override policy.
    $state = app(ModuleSettings::class)->read('runa.auth');
    app(ModuleSettings::class)->update('runa.auth', ['secondary_emails_enabled' => true], $state['revision'], $state['generation']);
    $input = ['command' => 'add', 'public_id' => $user->public_id, 'revision' => (string) $user->version, 'values' => ['email' => 'secondary-admin@example.test']];
    $this->postJson('/api/admin/runa.auth/users/sections/secondary/commands', $input)->assertOk();
    $record = authAdministrationRead($this, 'secondary', $user->public_id);
    $addresses = json_decode($record['values']['addresses'], true);
    expect($addresses)->toHaveCount(1)->and($addresses[0]['verified'])->toBeFalse();
    $input['revision'] = $record['revision'];
    $input['command'] = 'change';
    $input['values'] = ['address_id' => $addresses[0]['public_id'], 'email' => 'changed-secondary@example.test'];
    $this->postJson('/api/admin/runa.auth/users/sections/secondary/commands', $input)->assertOk();
    $record = authAdministrationRead($this, 'secondary', $user->public_id);
    $input['revision'] = $record['revision'];
    $input['command'] = 'remove';
    $input['confirmed'] = true;
    unset($input['values']['email']);
    $this->postJson('/api/admin/runa.auth/users/sections/secondary/commands', $input)->assertOk();
    $preferences = authAdministrationRead($this, 'preferences', $user->public_id);
    $reset = ['command' => 'reset', 'public_id' => $user->public_id, 'revision' => $preferences['revision'], 'confirmed' => true, 'values' => ['preference_revision' => $preferences['values']['preference_revision']]];
    $this->postJson('/api/admin/runa.auth/users/sections/preferences/commands', $reset)->assertOk();
    $this->postJson('/api/admin/runa.auth/users/sections/preferences/commands', $reset)->assertConflict();
});

it('Auth administration refuses stronger operations without native password proof', function (string $section, string $command, array $values, string $grant): void {
    $settings = app(ModuleSettings::class)->read('runa.auth');
    app(ModuleSettings::class)->update('runa.auth', ['email_verification' => 'new', 'admin_email_verification_bypass' => true], $settings['revision'], $settings['generation']);
    $target = app(ChangeIdentity::class)->create(new NewIdentity('strong-admin@example.test', name: 'Strong target', active: true));
    authAdministrationLogin($this, ['runa.auth.profiles.read', 'runa.auth.policy.exceptions', 'runa.auth.'.$grant]);
    if ($section === 'exceptions') {
        $values += ['reason' => 'Temporary reviewed exception', 'expires_at' => now()->utc()->addDay()->format('Y-m-d\TH:i:s\Z'), 'policy_revision' => app(ModuleSettings::class)->read('runa.auth')['revision']];
    }
    $this->postJson('/api/admin/runa.auth/users/sections/'.$section.'/commands', ['command' => $command, 'public_id' => $target->publicId, 'revision' => '1', 'values' => $values, 'confirmed' => true])->assertForbidden()->assertJsonPath('reason', 'step_up_required');
    expect(User::where('public_id', $target->publicId)->value('version'))->toBe(1);
})->with([
    ['primary_email', 'change', ['email' => 'new-strong@example.test'], 'emails.manage'],
    ['primary_email', 'replace', ['replacement_email' => 'new-strong@example.test'], 'emails.verify_bypass'],
    ['primary_email', 'require_verification', [], 'emails.reverify'],
    ['security', 'password_reset', [], 'passwords.reset'],
    ['security', 'factor_reset', [], 'two_factor.reset'],
    ['exceptions', 'set', ['key' => 'session_idle_minutes', 'value' => 180, 'exception_version' => 0], 'policy.exceptions'],
]);

it('Auth administration exposes bounded policy reads history reviews and stale publication refusal', function (): void {
    authAdministrationLogin($this, ['runa.auth.policy.read', 'runa.auth.policy.write']);
    $page = $this->getJson('/api/admin/runa.auth/policy')->assertOk()->json('data');
    expect($page['sections'])->toHaveCount(2);
    $welcome = collect($page['sections'][0]['fields'])->firstWhere('key', 'public_welcome_enabled');
    $definition = (new IdentitySettings)->settings()[0];
    expect($welcome['label'])->toBe($definition->names)
        ->and($welcome['description'])->toBe($definition->descriptions);
    $record = $this->getJson('/api/admin/runa.auth/policy/sections/policy')->assertOk()->json('data');
    $values = $record['values'];
    unset($values['summary']);
    $values['public_welcome_enabled'] = ! $values['public_welcome_enabled'];
    $input = ['command' => 'publish', 'public_id' => null, 'revision' => $record['revision'], 'values' => $values];
    $review = $this->postJson('/api/admin/runa.auth/policy/sections/policy/preview', $input)->assertOk()->json('data');
    expect($review['changes'])->toHaveKey('summary')->and($review['requirement'])->toBe('password');
    $input['review'] = $review;
    $this->postJson('/api/admin/runa.auth/policy/sections/policy/commands', $input)->assertForbidden()->assertJsonPath('error.code', 'step_up_required');
    $input['revision'] = '0:0';
    $this->postJson('/api/admin/runa.auth/policy/sections/policy/preview', $input)->assertConflict();
    $this->getJson('/api/admin/runa.auth/policy/sections/history')->assertNotFound();
});

function authAdministrationStrongProof(object $test, string $action): void
{
    $test->postJson('/api/runa/auth/step-up', ['action' => $action, 'current_password' => 'admin-entry-password'])->assertOk();
}

it('Auth administration writes indefinite rules and future dates beyond thirty days through native proof', function (): void {
    authAdministrationLogin($this, ['runa.auth.policy.exceptions']);
    expect($this->adminUser->fresh()->totp_secret)->toBeNull();
    $this->postJson('/api/runa/auth/step-up', ['action' => 'runa.auth.policy.exception', 'requirement' => 'both', 'method' => 'totp', 'code' => '123456', 'current_password' => 'admin-entry-password'])->assertUnprocessable();
    $page = $this->getJson('/api/admin/runa.auth/users')->assertOk()->json('data');
    $section = collect($page['sections'])->firstWhere('key', 'exceptions');
    expect(collect($section['fields'])->firstWhere('key', 'expires_at')['required'])->toBeFalse();
    foreach ([null, now()->addDays(90)->utc()->format('Y-m-d\TH:i:s\Z'), null] as $index => $expiry) {
        authAdministrationStrongProof($this, 'runa.auth.policy.exception');
        $record = authAdministrationRead($this, 'exceptions', $this->adminUser->public_id);
        $values = [
            'key' => 'session_idle_minutes', 'value' => $index === 2 ? null : 240,
            'reason' => 'Reviewed account rule', 'expires_at' => $expiry,
            'exception_version' => $index, 'policy_revision' => $record['values']['policy_revision'],
        ];
        $result = $this->postJson('/api/admin/runa.auth/users/sections/exceptions/commands', [
            'command' => 'set', 'public_id' => $this->adminUser->public_id,
            'revision' => $record['revision'], 'confirmed' => true, 'values' => $values,
        ])->assertOk()->json('data');
        expect($result['status'])->toBe('committed');
        $fresh = authAdministrationRead($this, 'exceptions', $this->adminUser->public_id);
        $active = json_decode($fresh['values']['active_exceptions'], true);
        if ($index === 2) {
            expect($active)->toBe([]);
        } else {
            expect($active)->toHaveCount(1)->and($active[0]['expires_at'] === null)->toBe($expiry === null);
        }
    }
});

it('Auth administration publishes through the native policy runner and refuses history restoration', function (): void {
    authAdministrationLogin($this, ['runa.auth.policy.read', 'runa.auth.policy.write']);
    authAdministrationStrongProof($this, 'runa.auth.policy.publish');
    $record = $this->getJson('/api/admin/runa.auth/policy/sections/policy')->assertOk()->json('data');
    $values = $record['values'];
    unset($values['summary']);
    $values['public_welcome_enabled'] = ! $values['public_welcome_enabled'];
    $input = ['command' => 'publish', 'public_id' => null, 'revision' => $record['revision'], 'values' => $values];
    $input['review'] = $this->postJson('/api/admin/runa.auth/policy/sections/policy/preview', $input)->assertOk()->json('data');
    $this->postJson('/api/admin/runa.auth/policy/sections/policy/commands', $input)->assertOk()->assertJsonPath('data.status', 'committed');
    expect(app(ModuleSettings::class)->value('runa.auth', 'public_welcome_enabled'))->toBe($values['public_welcome_enabled'])
        ->and(DB::table('runa_auth.step_up_proofs')->where('action', 'runa.auth.policy.publish')->whereNotNull('consumed_at')->exists())->toBeTrue();
    $this->postJson('/api/admin/runa.auth/policy/sections/policy/commands', $input)->assertConflict();
    $this->getJson('/api/admin/runa.auth/policy/sections/history')->assertNotFound();
});

it('Auth administration confirms selected bulk revocation without another identity proof', function (): void {
    $settings = app(ModuleSettings::class)->read('runa.auth');
    app(ModuleSettings::class)->update('runa.auth', ['trusted_devices_enabled' => true], $settings['revision'], $settings['generation']);
    $target = app(ChangeIdentity::class)->create(new NewIdentity('bulk-admin@example.test', name: 'Bulk target', active: true));
    authAdministrationLogin($this, ['runa.auth.sessions.bulk_revoke', 'runa.permissions.users.select']);
    $input = ['command' => 'revoke', 'public_id' => null, 'revision' => '0', 'values' => ['public_ids' => [$target->publicId], 'trust' => true], 'confirmed' => true];
    $input['review'] = $this->postJson('/api/admin/runa.auth/users/sections/bulk_sessions/preview', $input)->assertOk()->json('data');
    expect($input['review']['changes']['summary']['accounts'])->toBe(1);
    $stale = $input;
    $stale['review']['fingerprint'] = str_repeat('0', 64);
    $this->postJson('/api/admin/runa.auth/users/sections/bulk_sessions/commands', $stale)->assertConflict();
    expect($input['review']['requirement'])->toBe('none')
        ->and($input['review']['changes']['summary']['affected_accounts'])->toBe(0);
    $this->postJson('/api/admin/runa.auth/users/sections/bulk_sessions/commands', $input)->assertOk()->assertJsonPath('data.status', 'committed');
    expect(DB::table('runa_auth.step_up_proofs')->where('action', 'runa.auth.sessions.bulk_revoke')->exists())->toBeFalse();
    $input['values'] = ['public_ids' => [$target->publicId], 'all' => true, 'trust' => false];
    $this->postJson('/api/admin/runa.auth/users/sections/bulk_sessions/preview', $input)->assertUnprocessable();
});

it('Auth administration never treats a read grant as write authority', function (): void {
    $target = app(ChangeIdentity::class)->create(new NewIdentity('readonly-admin@example.test', name: 'Read only'));
    authAdministrationLogin($this, ['runa.auth.users.read', 'runa.auth.profiles.read', 'runa.auth.secondary.read', 'runa.auth.policy.read']);
    $page = $this->getJson('/api/admin/runa.auth/users')->assertOk()->json('data');
    foreach ($page['sections'] as $section) {
        expect($section['commands'])->toBe([]);
    }
    foreach (AdministrationPages::commands() as $section => $commands) {
        if ($section === 'bulk_sessions') {
            continue;
        }
        foreach ($commands as $key => $spec) {
            $result = $this->postJson('/api/admin/runa.auth/users/sections/'.$section.'/commands', ['command' => $key, 'public_id' => $target->publicId, 'revision' => '1', 'confirmed' => true, 'values' => array_fill_keys($spec['inputs'], null)]);
            expect($result->status())->toBeIn([404, 422]);
        }
    }
    expect(User::where('public_id', $target->publicId)->value('version'))->toBe(1);
});

it('Auth administration preserves native session and trust scoping without disclosing session credentials', function (): void {
    $target = app(ChangeIdentity::class)->create(new NewIdentity('sessions-admin@example.test', name: 'Session target', active: true));
    $user = User::where('public_id', $target->publicId)->firstOrFail();
    $sessions = DB::transaction(fn () => [
        app(SessionStore::class)->admit(DB::connection(), $user, 'auth-admin-session-one', false),
        app(SessionStore::class)->admit(DB::connection(), $user, 'auth-admin-session-two', false),
    ]);
    $deviceId = (string) Str::uuid7();
    DB::table('runa_auth.trusted_devices')->insert(['public_id' => $deviceId, 'user_id' => $user->id, 'browser' => 'Firefox', 'platform' => 'Linux', 'last_used_at' => now(), 'token_hash' => hash('sha256', 'auth-admin-disposable-device'), 'security_version' => $user->security_version, 'factor_method' => 'totp', 'created_at' => now(), 'expires_at' => now()->addDay()]);
    authAdministrationLogin($this, ['runa.auth.sessions.manage', 'runa.auth.trust.manage']);
    $record = authAdministrationRead($this, 'sessions', $target->publicId);
    expect(json_decode($record['values']['sessions'], true))->toHaveCount(2)
        ->and(json_encode($record))->not->toContain('session_hash', 'auth-admin-session-one', 'security_version');
    $this->postJson('/api/admin/runa.auth/users/sections/sessions/commands', ['command' => 'revoke', 'public_id' => $target->publicId, 'revision' => $record['revision'], 'values' => ['session_id' => $sessions[0]], 'confirmed' => true])->assertOk();
    $remaining = authAdministrationRead($this, 'sessions', $target->publicId);
    expect(json_decode($remaining['values']['sessions'], true))->toHaveCount(1);
    $this->postJson('/api/admin/runa.auth/users/sections/sessions/commands', ['command' => 'revoke_all', 'public_id' => $target->publicId, 'revision' => $remaining['revision'], 'values' => [], 'confirmed' => true])->assertOk();
    expect(json_decode(authAdministrationRead($this, 'sessions', $target->publicId)['values']['sessions'], true))->toBe([]);
    $trust = authAdministrationRead($this, 'trust', $target->publicId);
    expect(json_decode($trust['values']['devices'], true)[0]['public_id'])->toBe($deviceId)->and(json_decode($trust['values']['devices'], true)[0]['browser'])->toBe('Firefox')->and(json_decode($trust['values']['devices'], true)[0]['platform'])->toBe('Linux')->and(json_decode($trust['values']['devices'], true)[0]['current'])->toBeFalse()->and(json_encode($trust))->not->toContain('token_hash', 'factor_method');
    $this->postJson('/api/admin/runa.auth/users/sections/trust/commands', ['command' => 'revoke_device', 'public_id' => $target->publicId, 'revision' => $trust['revision'], 'values' => ['device_id' => $deviceId], 'confirmed' => true])->assertOk();
    expect(json_decode(authAdministrationRead($this, 'trust', $target->publicId)['values']['devices'], true))->toBe([]);
});

it('Auth administration runs native strong account actions with one use proof', function (string $section, string $command, array $values, string $grant, string $proof): void {
    $target = app(ChangeIdentity::class)->create(new NewIdentity('strong-success@example.test', name: 'Strong target', active: true));
    authAdministrationLogin($this, ['runa.auth.profiles.read', 'runa.auth.policy.exceptions', 'runa.auth.'.$grant]);
    if ($command === 'replace') {
        $state = app(ModuleSettings::class)->read('runa.auth');
        app(ModuleSettings::class)->update('runa.auth', ['admin_email_verification_bypass' => true], $state['revision'], $state['generation']);
    }
    if ($section === 'exceptions') {
        $values += ['reason' => 'Temporary reviewed exception', 'expires_at' => now()->utc()->addDay()->format('Y-m-d\TH:i:s\Z'), 'policy_revision' => app(ModuleSettings::class)->read('runa.auth')['revision']];
    }
    authAdministrationStrongProof($this, $proof);
    $input = ['command' => $command, 'public_id' => $target->publicId, 'revision' => '1', 'values' => $values, 'confirmed' => true];
    $this->postJson('/api/admin/runa.auth/users/sections/'.$section.'/commands', $input)->assertOk()->assertJsonPath('data.status', 'committed');
    expect(DB::table('runa_auth.step_up_proofs')->where('action', $proof)->whereNotNull('consumed_at')->exists())->toBeTrue();
    $this->postJson('/api/admin/runa.auth/users/sections/'.$section.'/commands', $input)->assertForbidden()->assertJsonPath('reason', 'step_up_required');
})->with([
    ['primary_email', 'change', ['email' => 'new-success@example.test'], 'emails.manage', 'runa.auth.email.admin_change'],
    ['primary_email', 'replace', ['replacement_email' => 'new-success@example.test'], 'emails.verify_bypass', 'runa.auth.email.admin_bypass'],
    ['primary_email', 'require_verification', [], 'emails.reverify', 'runa.auth.email.require_verification'],
    ['security', 'password_reset', [], 'passwords.reset', 'runa.auth.password.admin_reset'],
    ['security', 'factor_reset', [], 'two_factor.reset', 'runa.auth.two_factor.reset'],
    ['exceptions', 'set', ['key' => 'session_idle_minutes', 'value' => 180, 'exception_version' => 0], 'policy.exceptions', 'runa.auth.policy.exception'],
]);

it('Auth administration maps cleared optional naming fields to the native nullable contract', function (): void {
    $target = app(ChangeIdentity::class)->create(new NewIdentity('nullable-admin@example.test', name: 'Old name', login: 'old_login'));
    authAdministrationLogin($this, ['runa.auth.profiles.manage', 'runa.auth.users.create']);
    $outcome = app(PrincipalScope::class)->run(new TrustedPrincipal($this->adminUser->public_id), fn () => app(ProfileEditor::class)->execute(new ManagementWrite('save', $target->publicId, '1', ['name' => '', 'first_name' => 'New', 'surname' => 'Name', 'login' => ''])));
    expect($outcome->status)->toBe('committed')->and($outcome->record->values)->toBe(['name' => null, 'first_name' => 'New', 'surname' => 'Name', 'login' => null]);
    ($this->adminProof)('runa.auth.users.create');
    $this->postJson('/api/admin/runa.auth/users/sections/creation/commands', ['command' => 'create', 'public_id' => null, 'revision' => '0',
        'values' => authCreationValues(['email' => 'nullable-create@example.test', 'name' => 'Created name', 'first_name' => '', 'surname' => '', 'login' => ''])])->assertOk();
    expect(User::where('email', 'nullable-create@example.test')->value('login'))->toBeNull();
});

function authCreationValues(array $patch = []): array
{
    return array_replace(['email' => 'configured-create@example.test', 'name' => 'Configured user',
        'first_name' => null, 'surname' => null, 'login' => null, 'active' => false,
        'credential_mode' => 'none', 'email_verified' => false, 'activate_at' => null, 'deactivate_at' => null,
        'security_locked' => false, 'attempt_locked_until' => null, 'password_change_required' => false], $patch);
}

it('Auth creation verification defaults on and remains configurable in the Auth policy view', function (): void {
    authAdministrationLogin($this, ['runa.auth.users.create', 'runa.auth.emails.verify_bypass', 'runa.auth.policy.read', 'runa.auth.policy.write']);
    $settings = app(ModuleSettings::class);
    expect($settings->boolean('runa.auth', 'admin_email_verification_bypass'))->toBeTrue();
    $page = $this->getJson('/api/admin/runa.auth/policy')->assertOk()->json('data');
    $fields = array_merge(...array_column($page['sections'], 'fields'));
    $field = array_values(array_filter($fields, fn ($field) => $field['key'] === 'admin_email_verification_bypass'))[0];
    expect($field['type'])->toBe('boolean')->and($field['readonly'])->toBeFalse()
        ->and($field['label']['pl'])->toBe('Administracyjne potwierdzanie adresów e-mail');
    expect(authAdministrationRead($this, 'creation')['values']['email_verification_allowed'])->toBeTrue();
    $state = $settings->read('runa.auth');
    $settings->update('runa.auth', ['admin_email_verification_bypass' => false], $state['revision'], $state['generation']);
    expect(authAdministrationRead($this, 'creation')['values']['email_verification_allowed'])->toBeFalse();
    $this->postJson('/api/admin/runa.auth/users/sections/creation/preview', ['command' => 'create', 'public_id' => null, 'revision' => '0',
        'values' => authCreationValues(['email_verified' => true])])->assertForbidden()->assertJsonPath('error.code', 'email_bypass_disabled');
});

it('Auth creation atomically configures credentials schedules verification and locks without delivery', function (): void {
    authAdministrationLogin($this, ['runa.auth.users.create', 'runa.auth.emails.verify_bypass']);
    DB::transaction(function (): void {
        $this->adminUser->email_verified_at = now();
        $this->adminUser->save();
    });
    $settings = app(ModuleSettings::class)->read('runa.auth');
    app(ModuleSettings::class)->update('runa.auth', ['email_verification' => 'all', 'admin_email_verification_bypass' => true], $settings['revision'], $settings['generation']);
    authAdministrationStrongProof($this, 'runa.auth.users.create');
    $password = 'initial-user-password';
    $input = ['command' => 'create', 'public_id' => null, 'revision' => '0', 'values' => authCreationValues([
        'credential_mode' => 'password', 'password' => $password, 'password_confirmation' => $password,
        'email_verified' => true, 'activate_at' => now()->addDay()->utc()->format('Y-m-d\TH:i:s\Z'),
        'deactivate_at' => now()->addDays(2)->utc()->format('Y-m-d\TH:i:s\Z'),
        'security_locked' => true, 'attempt_locked_until' => now()->addHour()->utc()->format('Y-m-d\TH:i:s\Z'),
        'password_change_required' => true,
    ])];
    $preview = $this->postJson('/api/admin/runa.auth/users/sections/creation/preview', $input)->assertOk()->assertJsonPath('data.requirement', 'both');
    expect($preview->getContent())->not->toContain($password);
    $response = $this->postJson('/api/admin/runa.auth/users/sections/creation/commands', $input)->assertOk()
        ->assertJsonPath('data.record.values.password', ['set' => false]);
    $user = User::where('email', 'configured-create@example.test')->firstOrFail();
    expect(Hash::check($password, $user->password))->toBeTrue()
        ->and($user->active)->toBeFalse()->and($user->email_verified_at)->not->toBeNull()
        ->and($user->activate_at)->not->toBeNull()->and($user->deactivate_at)->not->toBeNull()
        ->and($user->security_locked)->toBeTrue()->and($user->password_change_required)->toBeTrue()
        ->and(DB::table('runa_auth.entry_tokens')->where('user_id', $user->id)->count())->toBe(0)
        ->and(DB::table('runa_auth.security_notices')->where('user_id', $user->id)->count())->toBe(0)
        ->and($response->getContent())->not->toContain($password, $user->password);
    $history = DB::table('runa_audit.events')->whereIn('action', ['identity.created', 'email.admin_verified', 'password.initial_set'])->get()->toJson();
    expect($history)->toContain('email.admin_verified', 'password.initial_set')->not->toContain($password, $user->password);
    expect(DB::table('runa_auth.step_up_proofs')->where('action', 'runa.auth.users.create')->whereNotNull('consumed_at')->exists())->toBeTrue();
});

it('Auth creation queues inactive invitations atomically and rolls back proof and account on Audit refusal', function (): void {
    authAdministrationLogin($this, ['runa.auth.users.create', 'runa.auth.invitations.manage']);
    ($this->adminProof)('runa.auth.users.create');
    $input = ['command' => 'create', 'public_id' => null, 'revision' => '0',
        'values' => authCreationValues(['credential_mode' => 'invitation'])];
    DB::statement('ALTER TABLE runa_audit.events ADD CONSTRAINT creation_fixture_refusal CHECK (false) NOT VALID');
    $this->postJson('/api/admin/runa.auth/users/sections/creation/commands', $input)->assertServiceUnavailable();
    expect(User::where('email', 'configured-create@example.test')->exists())->toBeFalse()
        ->and(DB::table('runa_auth.entry_tokens')->where('purpose', 'invitation')->exists())->toBeFalse()
        ->and(DB::table('runa_auth.step_up_proofs')->where('action', 'runa.auth.users.create')->whereNull('consumed_at')->exists())->toBeTrue();
    DB::statement('ALTER TABLE runa_audit.events DROP CONSTRAINT creation_fixture_refusal');
    $this->postJson('/api/admin/runa.auth/users/sections/creation/commands', $input)->assertOk();
    $user = User::where('email', 'configured-create@example.test')->firstOrFail();
    expect($user->active)->toBeFalse()->and($user->password)->toBeNull()
        ->and(DB::table('runa_auth.entry_tokens')->where('user_id', $user->id)->where('purpose', 'invitation')->count())->toBe(1)
        ->and(DB::table('shared.module_work')->where('operation', 'entry.deliver')->exists())->toBeTrue();
});

it('Auth creation refuses unsupported credentials schedules and verification without partial accounts', function (): void {
    authAdministrationLogin($this, ['runa.auth.users.create']);
    $settings = app(ModuleSettings::class)->read('runa.auth');
    app(ModuleSettings::class)->update('runa.auth', ['password_expiry_days' => 0], $settings['revision'], $settings['generation']);
    $page = $this->getJson('/api/admin/runa.auth/users')->assertOk()->json('data');
    $creation = array_values(array_filter($page['sections'], fn ($section) => $section['key'] === 'creation'))[0];
    expect(array_column($creation['commands'], 'key'))->toContain('create')
        ->and(array_column($creation['fields'], 'key'))->toContain('password_change_required');
    ($this->adminProof)('runa.auth.users.create');
    $input = ['command' => 'create', 'public_id' => null, 'revision' => '0', 'values' => authCreationValues()];
    $this->postJson('/api/admin/runa.auth/users/sections/creation/commands', array_replace($input, ['values' => authCreationValues(['email_verified' => true])]))->assertForbidden();
    $this->postJson('/api/admin/runa.auth/users/sections/creation/commands', array_replace($input, ['values' => authCreationValues(['credential_mode' => 'invitation'])]))->assertForbidden();
    $this->postJson('/api/admin/runa.auth/users/sections/creation/commands', array_replace($input, ['values' => authCreationValues(['credential_mode' => 'password', 'password' => 'short', 'password_confirmation' => 'short'])]))->assertUnprocessable();
    $this->postJson('/api/admin/runa.auth/users/sections/creation/commands', array_replace($input, ['values' => authCreationValues([
        'activate_at' => now()->addDays(2)->utc()->format('Y-m-d\TH:i:s\Z'), 'deactivate_at' => now()->addDay()->utc()->format('Y-m-d\TH:i:s\Z'),
    ])]))->assertConflict()->assertJsonPath('error.code', 'invalid_schedule_order');
    expect(User::where('email', 'configured-create@example.test')->exists())->toBeFalse()
        ->and(DB::table('runa_auth.step_up_proofs')->where('action', 'runa.auth.users.create')->whereNull('consumed_at')->exists())->toBeTrue();
});

function permissionAdministrationRead(object $test, string $page, string $section, ?string $id = null, array $query = []): array
{
    return $test->getJson('/api/admin/runa.permissions/'.$page.'/sections/'.$section.'?'.http_build_query([
        ...($id === null ? [] : ['public_id' => $id]), ...$query,
    ]))->assertOk()->json('data');
}

function authPermissionsRead(object $test, string $section, string $id): array
{
    return $test->getJson('/api/admin/runa.auth/users/sections/'.$section.'?public_id='.$id)->assertOk()->json('data');
}

function permissionDefinitionInput(string $kind, string $code, array $permissions = [], array $roles = []): array
{
    return ['command' => 'store', 'public_id' => null, 'revision' => '0', 'values' => [
        'code' => 'custom.'.$code, 'name_pl' => 'Wsparcie', 'name_en' => 'Support', 'description_pl' => 'Dostep wsparcia.', 'description_en' => 'Support access.',
        'permissions' => $permissions, ...($kind === 'preset' ? ['roles' => $roles] : []),
    ]];
}

function permissionAdministrationReview(object $test, string $path, array $input): array
{
    $input['review'] = $test->postJson($path.'/preview', $input)->assertOk()->json('data');
    expect($input['review']['requirement'])->toBe('password')->and($input['review']['changes'])->not->toBeEmpty();

    return $input;
}

it('Permissions Admin adapts reviewed custom definitions with native proof stale rejection and Audit rollback', function (): void {
    authAdministrationLogin($this, ['runa.permissions.introspection.read', 'runa.permissions.access.manage']);
    $path = '/api/admin/runa.permissions/roles/sections/editor';
    $permission = DB::table('runa_permissions.permissions')->where('code', 'runa.audit.read')->value('public_id');
    $input = permissionAdministrationReview($this, $path, permissionDefinitionInput('role', 'admin_support', [$permission]));
    $this->postJson($path.'/commands', $input)->assertForbidden();
    authAdministrationStrongProof($this, 'runa.permissions.access.manage');
    DB::statement("ALTER TABLE runa_audit.events ADD CONSTRAINT admin_role_audit_failure CHECK (action <> 'role.saved') NOT VALID");
    try {
        $this->postJson($path.'/commands', $input)->assertServiceUnavailable();
        expect(DB::table('runa_permissions.roles')->where('code', 'custom.admin_support')->exists())->toBeFalse()
            ->and(DB::table('runa_auth.step_up_proofs')->where('action', 'runa.permissions.access.manage')->value('consumed_at'))->toBeNull();
    } finally {
        DB::statement('ALTER TABLE runa_audit.events DROP CONSTRAINT admin_role_audit_failure');
    }
    $record = $this->postJson($path.'/commands', $input)->assertOk()->assertJsonPath('data.status', 'committed')->json('data.record');
    expect($record['values']['permissions'])->toBe([$permission]);
    $this->postJson($path.'/commands', $input)->assertConflict();
    $edit = ['command' => 'store', 'public_id' => $record['public_id'], 'revision' => $record['revision'], 'values' => $input['values']];
    $edit['values']['name_en'] = 'Changed support';
    $edit = permissionAdministrationReview($this, $path, $edit);
    authAdministrationStrongProof($this, 'runa.permissions.access.manage');
    $updated = $this->postJson($path.'/commands', $edit)->assertOk()->json('data.record');
    $this->postJson($path.'/commands', $edit)->assertConflict();
    $delete = permissionAdministrationReview($this, $path, ['command' => 'delete', 'public_id' => $updated['public_id'], 'revision' => $updated['revision'], 'values' => [], 'confirmed' => true]);
    authAdministrationStrongProof($this, 'runa.permissions.access.manage');
    $this->postJson($path.'/commands', $delete)->assertOk()->assertJsonPath('data.record.public_id', null);
    expect(DB::table('runa_audit.events')->where('action', 'role.saved')->count())->toBe(2)
        ->and(DB::table('runa_audit.events')->where('action', 'role.deleted')->count())->toBe(1);
    $this->postJson('/api/admin/runa.permissions/permissions/sections/editor/commands', $input)->assertUnprocessable();
});

it('Auth user details review copy and presets through native assignment fingerprints and protect membership', function (): void {
    authAdministrationLogin($this, ['runa.auth.users.read', 'runa.permissions.introspection.read', 'runa.permissions.access.manage', 'runa.auth.eligibility.read']);
    $target = app(ChangeIdentity::class)->create(new NewIdentity('permissions-admin-target@example.test', name: 'Target', active: true));
    $source = app(ChangeIdentity::class)->create(new NewIdentity('permissions-admin-source@example.test', name: 'Source', active: true));
    $permission = DB::table('runa_permissions.permissions')->where('code', 'runa.audit.read')->value('public_id');
    $path = '/api/admin/runa.auth/users/sections/runa.permissions.user_access.assignments';
    $read = fn (string $id): array => authPermissionsRead($this, 'runa.permissions.user_access.assignments', $id);
    $input = permissionAdministrationReview($this, $path, ['command' => 'save', 'public_id' => $source->publicId, 'revision' => $read($source->publicId)['revision'], 'values' => ['roles' => [], 'permissions' => [$permission]]]);
    authAdministrationStrongProof($this, 'runa.permissions.access.manage');
    $this->postJson($path.'/commands', $input)->assertOk();
    $presetPath = '/api/admin/runa.permissions/presets/sections/editor';
    $preset = permissionAdministrationReview($this, $presetPath, permissionDefinitionInput('preset', 'admin_snapshot', [$permission]));
    authAdministrationStrongProof($this, 'runa.permissions.access.manage');
    $presetId = $this->postJson($presetPath.'/commands', $preset)->assertOk()->json('data.record.public_id');
    foreach ([['copy', ['source_user' => $source->publicId, 'mode' => 'add']], ['preset', ['preset' => $presetId, 'mode' => 'replace']], ['save', ['roles' => [], 'permissions' => []]]] as [$command, $values]) {
        $input = permissionAdministrationReview($this, $path, ['command' => $command, 'public_id' => $target->publicId, 'revision' => $read($target->publicId)['revision'], 'values' => $values]);
        authAdministrationStrongProof($this, 'runa.permissions.access.manage');
        $result = $this->postJson($path.'/commands', $input)->assertOk()->json('data.record.values.permissions');
        expect($result)->toBe($command === 'save' ? [] : [$permission]);
    }
    // A reviewed copy cannot silently use changed source assignments.
    $copy = permissionAdministrationReview($this, $path, ['command' => 'copy', 'public_id' => $target->publicId, 'revision' => $read($target->publicId)['revision'], 'values' => ['source_user' => $source->publicId, 'mode' => 'replace']]);
    $clear = permissionAdministrationReview($this, $path, ['command' => 'save', 'public_id' => $source->publicId, 'revision' => $read($source->publicId)['revision'], 'values' => ['roles' => [], 'permissions' => []]]);
    authAdministrationStrongProof($this, 'runa.permissions.access.manage');
    $this->postJson($path.'/commands', $clear)->assertOk();
    authAdministrationStrongProof($this, 'runa.permissions.access.manage');
    $this->postJson($path.'/commands', $copy)->assertConflict();
    expect($read($target->publicId)['values']['permissions'])->toBe([]);
    $protected = DB::table('runa_permissions.roles')->where('system', true)->value('public_id');
    $this->postJson($path.'/preview', ['command' => 'save', 'public_id' => $target->publicId, 'revision' => $read($target->publicId)['revision'], 'values' => ['roles' => [$protected], 'permissions' => []]])->assertUnprocessable();
});

it('Audit Admin keeps cursor summaries payload free and detail immutable behind current authority', function (): void {
    authAdministrationLogin($this, ['runa.audit.read']);
    $subject = (string) Str::uuid7();
    DB::transaction(function () use ($subject): void {
        app(ModuleApis::class)->resolve('runa.audit', AuditRecords::class, '1.0.0')->record(new AuditEvent('runa.audit', 'viewer.example',
            entities: [new AuditEntity('runa.auth', 'user', $subject)], changes: ['label' => ['old' => 'before', 'new' => 'safe-detail-only'], 'password' => ['old' => 'private-secret', 'new' => 'other-secret']]));
    });
    $path = '/api/admin/runa.audit/history/sections/';
    $query = ['kind' => 'page', 'limit' => 1, 'filters' => json_encode(['module' => 'runa.audit', 'action' => 'viewer.example'])];
    $page = $this->getJson($path.'catalogue?'.http_build_query($query))->assertOk()->json('data');
    expect($page['total'])->toBeNull()->and($page['items'])->toHaveCount(1)->and(json_encode($page))->not->toContain('safe-detail-only', 'private-secret', 'changes', 'execution', 'entities');
    $id = $page['items'][0]['public_id'];
    $detail = $this->getJson($path.'detail?public_id='.$id)->assertOk()->json('data');
    expect($detail['values']['changes'])->toContain('safe-detail-only')->not->toContain('private-secret', 'other-secret')
        ->and($detail['values']['entities'])->toContain($subject);
    $this->getJson($path.'catalogue?'.http_build_query(['kind' => 'page', 'filters' => json_encode(['entity_module' => 'runa.auth'])]))->assertUnprocessable();
    $this->getJson($path.'catalogue?kind=page&cursor=invalid')->assertUnprocessable();
    $this->getJson($path.'detail?public_id='.(string) Str::uuid7())->assertNotFound();
    $this->postJson($path.'detail/commands', ['command' => 'delete', 'public_id' => $id, 'revision' => $id, 'values' => []])->assertUnprocessable();
    DB::table('runa_permissions.user_permissions')->where('user_public_id', $this->adminUser->public_id)->where('permission_id', DB::table('runa_permissions.permissions')->where('code', 'runa.audit.read')->value('id'))->delete();
    $this->getJson($path.'detail?public_id='.$id)->assertNotFound()->assertJsonMissingPath('data');
});

it('Permissions Admin separates protected authority and retains the last eligible administrator', function (): void {
    authAdministrationLogin($this, ['runa.auth.users.read', 'runa.permissions.authority.manage']);
    $target = app(ChangeIdentity::class)->create(new NewIdentity('protected-admin-target@example.test', name: 'Administration target', active: true));
    $path = '/api/admin/runa.auth/users/sections/runa.permissions.user_authority.authority';
    $own = authPermissionsRead($this, 'runa.permissions.user_authority.authority', $this->adminUser->public_id);
    $this->postJson($path.'/preview', ['command' => 'change', 'public_id' => $this->adminUser->public_id, 'revision' => $own['revision'], 'values' => ['grant' => true]])->assertForbidden();
    $record = authPermissionsRead($this, 'runa.permissions.user_authority.authority', $target->publicId);
    $input = permissionAdministrationReview($this, $path, ['command' => 'change', 'public_id' => $target->publicId, 'revision' => $record['revision'], 'values' => ['grant' => true]]);
    $this->postJson($path.'/commands', $input)->assertUnprocessable();
    $input['confirmed'] = true;
    $this->postJson($path.'/commands', $input)->assertForbidden();
    authAdministrationStrongProof($this, 'runa.permissions.authority.manage');
    $this->postJson($path.'/commands', $input)->assertOk();
    $this->postJson($path.'/commands', $input)->assertConflict();
    $record = authPermissionsRead($this, 'runa.permissions.user_authority.authority', $target->publicId);
    $remove = permissionAdministrationReview($this, $path, ['command' => 'change', 'public_id' => $target->publicId, 'revision' => $record['revision'], 'values' => ['grant' => false], 'confirmed' => true]);
    authAdministrationStrongProof($this, 'runa.permissions.authority.manage');
    $this->postJson($path.'/commands', $remove)->assertConflict();
    expect(DB::table('runa_permissions.user_roles')->where('user_public_id', $target->publicId)->exists())->toBeTrue()
        ->and(DB::table('runa_audit.events')->where('action', 'authority.removed')->count())->toBe(0)
        ->and(DB::table('runa_auth.step_up_proofs')->where('action', 'runa.permissions.authority.manage')->whereNull('consumed_at')->exists())->toBeTrue();
});

it('Permissions Admin authority batch grants and revokes the complete selected set with one proof and atomic Audit', function (): void {
    authAdministrationLogin($this, ['runa.auth.users.read', 'runa.permissions.authority.manage', 'runa.permissions.users.select']);
    $a = app(ChangeIdentity::class)->create(new NewIdentity('batch-authority-a@example.test', name: 'First target', active: true));
    $b = app(ChangeIdentity::class)->create(new NewIdentity('batch-authority-b@example.test', name: 'Second target', active: true));
    $path = '/api/admin/runa.auth/users/sections/runa.permissions.user_authority_bulk.bulk_authority';
    $input = permissionAdministrationReview($this, $path, ['command' => 'grant_superadmin', 'public_id' => null, 'revision' => '0', 'values' => ['public_ids' => [$b->publicId, $a->publicId]]]);
    expect($input['review']['changes']['summary']['affected_accounts'])->toBe(2);
    $this->postJson($path.'/commands', $input)->assertUnprocessable();
    $input['confirmed'] = true;
    $this->postJson($path.'/commands', $input)->assertForbidden();
    authAdministrationStrongProof($this, 'runa.permissions.authority.manage');
    // The second event fails, proving that the earlier target and its Audit also roll back.
    DB::statement("ALTER TABLE runa_audit.entities ADD CONSTRAINT admin_authority_batch_audit_failure CHECK (entity_public_id <> '".$b->publicId."') NOT VALID");
    try {
        $this->postJson($path.'/commands', $input)->assertServiceUnavailable();
        expect(DB::table('runa_permissions.user_roles')->whereIn('user_public_id', [$a->publicId, $b->publicId])->exists())->toBeFalse()
            ->and(DB::table('runa_audit.events')->where('action', 'authority.granted')->count())->toBe(0)
            ->and(DB::table('runa_permissions.user_access')->whereIn('user_public_id', [$a->publicId, $b->publicId])->exists())->toBeFalse()
            ->and(DB::table('runa_auth.step_up_proofs')->where('action', 'runa.permissions.authority.manage')->whereNull('consumed_at')->exists())->toBeTrue();
    } finally {
        DB::statement('ALTER TABLE runa_audit.entities DROP CONSTRAINT admin_authority_batch_audit_failure');
    }
    $this->postJson($path.'/commands', $input)->assertOk();
    expect(DB::table('runa_audit.events')->where('action', 'authority.granted')->count())->toBe(2)
        ->and(DB::table('runa_auth.step_up_proofs')->where('action', 'runa.permissions.authority.manage')->whereNull('consumed_at')->exists())->toBeFalse();
    $this->postJson($path.'/commands', $input)->assertConflict();
    $unchanged = permissionAdministrationReview($this, $path, $input);
    expect($unchanged['review']['changes']['summary']['affected_accounts'])->toBe(0)
        ->and($unchanged['review']['changes']['summary']['unchanged_accounts'])->toBe(2);
    authAdministrationStrongProof($this, 'runa.permissions.authority.manage');
    $this->postJson($path.'/commands', $unchanged)->assertOk();
    expect(DB::table('runa_audit.events')->where('action', 'authority.granted')->count())->toBe(2)
        ->and(DB::table('runa_permissions.user_access')->whereIn('user_public_id', [$a->publicId, $b->publicId])->pluck('version')->all())->toBe([1, 1]);
    // Retention is reviewed explicitly; an added outside administrator invalidates the plan.
    $remove = ['command' => 'revoke_superadmin', 'public_id' => null, 'revision' => '0', 'values' => ['public_ids' => [$a->publicId, $b->publicId]], 'confirmed' => true];
    $retained = permissionAdministrationReview($this, $path, $remove);
    expect($retained['review']['changes']['summary']['skipped_accounts'])->toBe(1)
        ->and($retained['review']['changes']['summary']['affected_accounts'])->toBe(1);
    $role = DB::table('runa_permissions.roles')->where('code', 'runa.permissions.super_admin')->value('id');
    DB::table('runa_permissions.user_roles')->insert(['user_public_id' => $this->adminUser->public_id, 'role_id' => $role, 'created_at' => now(), 'updated_at' => now()]);
    authAdministrationStrongProof($this, 'runa.permissions.authority.manage');
    $this->postJson($path.'/commands', $retained)->assertConflict();
    expect(DB::table('runa_permissions.user_roles')->where('role_id', $role)->whereIn('user_public_id', [$a->publicId, $b->publicId])->count())->toBe(2)
        ->and(DB::table('runa_auth.step_up_proofs')->where('action', 'runa.permissions.authority.manage')->whereNull('consumed_at')->exists())->toBeTrue();
    $remove = permissionAdministrationReview($this, $path, $remove);
    expect($remove['review']['changes']['summary']['affected_accounts'])->toBe(2)
        ->and($remove['review']['changes']['summary']['skipped_accounts'])->toBe(0);
    authAdministrationStrongProof($this, 'runa.permissions.authority.manage');
    $this->postJson($path.'/commands', $remove)->assertOk();
    expect(DB::table('runa_permissions.user_roles')->where('role_id', $role)->whereIn('user_public_id', [$a->publicId, $b->publicId])->exists())->toBeFalse()
        ->and(DB::table('runa_permissions.user_roles')->where('role_id', $role)->where('user_public_id', $this->adminUser->public_id)->exists())->toBeTrue()
        ->and(DB::table('runa_audit.events')->where('action', 'authority.removed')->count())->toBe(2);
});

it('Permissions Admin authority batch retains one eligible administrator and applies the remaining removals', function (): void {
    authAdministrationLogin($this, ['runa.auth.users.read', 'runa.permissions.authority.manage', 'runa.permissions.users.select']);
    $b = app(ChangeIdentity::class)->create(new NewIdentity('removed-inactive@example.test', name: 'Inactive administrator', active: false));
    $a = app(ChangeIdentity::class)->create(new NewIdentity('retained-active@example.test', name: 'Eligible administrator', active: true));
    $role = DB::table('runa_permissions.roles')->where('code', 'runa.permissions.super_admin')->value('id');
    foreach ([$a->publicId, $b->publicId] as $id) {
        DB::table('runa_permissions.user_roles')->insert(['user_public_id' => $id, 'role_id' => $role, 'created_at' => now(), 'updated_at' => now()]);
    }
    $path = '/api/admin/runa.auth/users/sections/runa.permissions.user_authority_bulk.bulk_authority';
    $input = ['command' => 'revoke_superadmin', 'public_id' => null, 'revision' => '0', 'values' => ['public_ids' => [$b->publicId, $a->publicId]], 'confirmed' => true];
    $input = permissionAdministrationReview($this, $path, $input);
    expect($input['review']['changes']['summary'])->toMatchArray(['accounts' => 2, 'affected_accounts' => 1, 'skipped_accounts' => 1, 'unchanged_accounts' => 0]);
    $this->postJson($path.'/commands', $input)->assertForbidden();
    authAdministrationStrongProof($this, 'runa.permissions.authority.manage');
    $this->postJson($path.'/commands', $input)->assertOk();
    expect(DB::table('runa_permissions.user_roles')->where('role_id', $role)->pluck('user_public_id')->all())->toBe([$a->publicId])
        ->and(DB::table('runa_permissions.user_access')->where('user_public_id', $a->publicId)->exists())->toBeFalse()
        ->and(DB::table('runa_permissions.user_access')->where('user_public_id', $b->publicId)->value('version'))->toBe(1)
        ->and(DB::table('runa_audit.events')->where('action', 'authority.removed')->count())->toBe(1);
    // Selecting only the remaining administrator is a reviewed skip, not a failed operation.
    $only = permissionAdministrationReview($this, $path, [...$input, 'values' => ['public_ids' => [$a->publicId]]]);
    expect($only['review']['changes']['summary'])->toMatchArray(['accounts' => 1, 'affected_accounts' => 0, 'skipped_accounts' => 1, 'unchanged_accounts' => 0]);
    authAdministrationStrongProof($this, 'runa.permissions.authority.manage');
    $this->postJson($path.'/commands', $only)->assertOk();
    expect(DB::table('runa_permissions.user_roles')->where('role_id', $role)->pluck('user_public_id')->all())->toBe([$a->publicId])
        ->and(DB::table('runa_audit.events')->where('action', 'authority.removed')->count())->toBe(1);
});

it('Permissions Admin authority batch rejects missing selection grants invalid targets stale reviews and self escalation', function (): void {
    authAdministrationLogin($this, ['runa.auth.users.read', 'runa.permissions.authority.manage']);
    $target = app(ChangeIdentity::class)->create(new NewIdentity('batch-refusal@example.test', name: 'Batch refusal target', active: true));
    $path = '/api/admin/runa.auth/users/sections/runa.permissions.user_authority_bulk.bulk_authority';
    $input = ['command' => 'grant_superadmin', 'public_id' => null, 'revision' => '0', 'values' => ['public_ids' => [$target->publicId]], 'confirmed' => true];
    $this->postJson($path.'/preview', $input)->assertUnprocessable();
    ($this->adminGrant)('runa.permissions.users.select');
    foreach ([[], [$target->publicId, $target->publicId], array_map(fn () => (string) Str::uuid7(), range(1, 101))] as $ids) {
        $this->postJson($path.'/preview', [...$input, 'values' => ['public_ids' => $ids]])->assertUnprocessable();
    }
    $this->postJson($path.'/preview', [...$input, 'values' => ['public_ids' => [$target->publicId, (string) Str::uuid7()]]])->assertNotFound();
    $this->postJson($path.'/preview', [...$input, 'values' => ['public_ids' => [$this->adminUser->public_id, $target->publicId]]])->assertForbidden();
    $input = permissionAdministrationReview($this, $path, $input);
    DB::table('runa_permissions.user_access')->updateOrInsert(['user_public_id' => $target->publicId], ['version' => 1, 'updated_at' => now()]);
    authAdministrationStrongProof($this, 'runa.permissions.authority.manage');
    $this->postJson($path.'/commands', $input)->assertConflict();
    expect(DB::table('runa_permissions.user_roles')->where('user_public_id', $target->publicId)->exists())->toBeFalse()
        ->and(DB::table('runa_auth.step_up_proofs')->where('action', 'runa.permissions.authority.manage')->whereNull('consumed_at')->exists())->toBeTrue();
});

it('Permissions Admin force deletion keeps its dedicated grant complete review confirmation and atomic Audit', function (): void {
    authAdministrationLogin($this, ['runa.auth.users.read', 'runa.permissions.introspection.read', 'runa.permissions.access.manage', 'runa.permissions.roles.force_delete', 'runa.auth.eligibility.read']);
    $permission = DB::table('runa_permissions.permissions')->where('code', 'runa.audit.read')->value('public_id');
    $path = '/api/admin/runa.permissions/roles/sections/editor';
    $input = permissionAdministrationReview($this, $path, permissionDefinitionInput('role', 'force_review', [$permission]));
    authAdministrationStrongProof($this, 'runa.permissions.access.manage');
    $role = $this->postJson($path.'/commands', $input)->assertOk()->json('data.record');
    $target = app(ChangeIdentity::class)->create(new NewIdentity('force-admin-target@example.test', name: 'Administration target', active: true));
    $assignmentPath = '/api/admin/runa.auth/users/sections/runa.permissions.user_access.assignments';
    $targetRead = authPermissionsRead($this, 'runa.permissions.user_access.assignments', $target->publicId);
    $assign = permissionAdministrationReview($this, $assignmentPath, ['command' => 'save', 'public_id' => $target->publicId, 'revision' => $targetRead['revision'], 'values' => ['roles' => [$role['public_id']], 'permissions' => []]]);
    authAdministrationStrongProof($this, 'runa.permissions.access.manage');
    $this->postJson($assignmentPath.'/commands', $assign)->assertOk();
    // Ordinary deletion keeps the native referenced-role refusal.
    $delete = permissionAdministrationReview($this, $path, ['command' => 'delete', 'public_id' => $role['public_id'], 'revision' => $role['revision'], 'values' => [], 'confirmed' => true]);
    authAdministrationStrongProof($this, 'runa.permissions.access.manage');
    $this->postJson($path.'/commands', $delete)->assertConflict();
    // A force-delete manager does not also need ordinary write authority.
    DB::table('runa_permissions.user_permissions')->where('user_public_id', $this->adminUser->public_id)->where('permission_id', DB::table('runa_permissions.permissions')->where('code', 'runa.permissions.access.manage')->value('id'))->delete();
    $forcePath = '/api/admin/runa.permissions/roles/sections/force';
    $force = permissionAdministrationReview($this, $forcePath, ['command' => 'force_delete', 'public_id' => $role['public_id'], 'revision' => $role['revision'], 'values' => []]);
    expect($force['review']['changes']['review_summary']['users'])->toHaveCount(1);
    $this->postJson($forcePath.'/commands', $force)->assertUnprocessable();
    $force['confirmed'] = true;
    authAdministrationStrongProof($this, 'runa.permissions.roles.force_delete');
    DB::statement("ALTER TABLE runa_audit.events ADD CONSTRAINT admin_force_audit_failure CHECK (action <> 'role.force_deleted') NOT VALID");
    try {
        $this->postJson($forcePath.'/commands', $force)->assertServiceUnavailable();
        expect(DB::table('runa_permissions.user_roles')->where('user_public_id', $target->publicId)->exists())->toBeTrue()
            ->and(DB::table('runa_auth.step_up_proofs')->where('action', 'runa.permissions.roles.force_delete')->value('consumed_at'))->toBeNull();
    } finally {
        DB::statement('ALTER TABLE runa_audit.events DROP CONSTRAINT admin_force_audit_failure');
    }
    $this->postJson($forcePath.'/commands', $force)->assertOk();
    expect(DB::table('runa_permissions.user_roles')->where('user_public_id', $target->publicId)->exists())->toBeFalse()
        ->and(DB::table('runa_audit.events')->where('action', 'role.force_deleted')->count())->toBe(1);
});

it('Permissions Admin source dictionaries and ordinary read grants never authorize mutation', function (): void {
    authAdministrationLogin($this, ['runa.permissions.introspection.read']);
    $source = DB::table('runa_permissions.roles')->where('system', true)->first();
    $detail = permissionAdministrationRead($this, 'roles', 'editor', $source->public_id);
    expect($detail['values']['system'])->toBeTrue();
    $input = permissionDefinitionInput('role', 'unauthorized');
    $this->postJson('/api/admin/runa.permissions/roles/sections/editor/preview', $input)->assertUnprocessable();
    ($this->adminGrant)('runa.permissions.access.manage');
    $this->postJson('/api/admin/runa.permissions/roles/sections/editor/preview', ['command' => 'delete', 'public_id' => $source->public_id, 'revision' => $detail['revision'], 'values' => []])->assertUnprocessable();
    $this->getJson('/api/admin/runa.permissions/permissions/sections/catalogue?kind=page&limit=1')->assertOk()->assertJsonPath('data.total', null);
    $this->getJson('/api/admin/runa.permissions/roles/sections/catalogue?kind=page&cursor=invalid')->assertUnprocessable()->assertJsonMissingPath('data');
});

it('Audit Admin pages complete large native detail without weakening management bounds', function (): void {
    authAdministrationLogin($this, ['runa.audit.read']);
    $tree = array_fill_keys(['one', 'two', 'three', 'four', 'five', 'six'], str_repeat('bounded-data ', 500));
    $reference = DB::transaction(fn () => app(ModuleApis::class)->resolve('runa.audit', AuditRecords::class, '1.0.0')->record(new AuditEvent('runa.audit', 'viewer.large', changes: $tree, context: $tree)));
    $parts = ['changes' => '', 'context' => '', 'execution' => ''];
    $cursor = null;
    do {
        $query = ['public_id' => $reference->publicId, ...($cursor === null ? [] : ['cursor' => $cursor])];
        $record = $this->getJson('/api/admin/runa.audit/history/sections/detail?'.http_build_query($query))->assertOk()->json('data');
        expect(strlen(json_encode($record)))->toBeLessThan(65536);
        foreach (array_keys($parts) as $key) {
            $parts[$key] .= $record['values'][$key];
        }
        $cursor = $record['values']['payload_next'];
    } while ($cursor !== null);
    expect(json_decode($parts['changes'], true))->toEqual($tree)->and(json_decode($parts['context'], true))->toEqual($tree);
    $this->getJson('/api/admin/runa.audit/history/sections/detail?public_id='.$reference->publicId.'&cursor=999')->assertUnprocessable();
});

it('exposes fixed operational diagnostics with exact grants redaction and maintenance fencing', function (): void {
    ($this->adminGrant)('runa.admin.enter');
    ($this->adminLogin)();
    ($this->adminProof)();
    $this->getJson('/api/admin/operations/capabilities')->assertOk()->assertJsonPath('data.capabilities', []);
    $this->getJson('/api/admin/operations/diagnostics')->assertForbidden();
    ($this->adminGrant)('runa.admin.diagnostics.read');
    $response = $this->getJson('/api/admin/operations/diagnostics')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    expect($response->json('data.modules'))->toHaveCount(4);
    foreach ($response->json('data.modules') as $module) {
        expect(array_keys($module))->not->toContain('source_path', 'manifest_hash', 'settings', 'config', 'payload', 'lease_token');
    }
    $this->getJson('/api/admin/operations/diagnostics?filter=invalid')->assertUnprocessable();
    $this->getJson('/api/admin/operations/diagnostics?actor=forged')->assertUnprocessable();
    $this->postJson('/api/admin/operations/health.diagnose', ['module' => 'runa.auth'])->assertForbidden();
    ($this->adminGrant)('runa.admin.health.diagnose');
    $this->postJson('/api/admin/operations/health.diagnose', ['module' => 'runa.auth'])->assertOk()->assertJsonPath('data.health', 'healthy');
    $this->postJson('/api/admin/operations/install', ['module' => 'runa.auth'])->assertUnprocessable();
    $this->postJson('/api/admin/operations/shell', ['command' => 'anything'])->assertUnprocessable();
    ($this->adminGrant)('runa.admin.modules.manage');
    $this->postJson('/api/admin/operations/modules.preview', ['module' => 'runa.auth', 'intent' => 'disable'])->assertUnprocessable();
    $marker = app()->storagePath('framework/maintenance/enabled');
    File::ensureDirectoryExists(dirname($marker));
    file_put_contents($marker, '');
    try {
        $this->getJson('/api/admin/operations/diagnostics')->assertServiceUnavailable();
        $this->postJson('/api/admin/operations/health.diagnose', ['module' => 'runa.auth'])->assertServiceUnavailable();
    } finally {
        unlink($marker);
    }
});

it('keeps Auth policy settings in their reviewed owner workflow and isolates read authority', function (): void {
    foreach (['runa.admin.enter', 'runa.admin.settings.manage'] as $code) {
        ($this->adminGrant)($code);
    }
    ($this->adminLogin)();
    ($this->adminProof)();
    $generation = app(ModuleRegistry::class)->snapshot()->generation;
    foreach (['runa.auth', 'runa.audit', 'runa.permissions', 'app.reference'] as $module) {
        $this->getJson('/api/admin/operations/settings?module='.$module)->assertUnprocessable();
        $this->postJson('/api/admin/operations/settings.update', ['module' => $module, 'generation' => $generation, 'revision' => 0, 'patch' => []])->assertUnprocessable();
    }
    expect(app(ModuleRegistry::class)->snapshot()->generation)->toBe($generation);
    $this->getJson('/api/admin/operations/work?module=runa.auth')->assertForbidden();
    ($this->adminGrant)('runa.admin.work.read');
    $this->getJson('/api/admin/operations/work?module=runa.auth&limit=1')->assertOk()->assertJsonStructure(['data' => ['work', 'truncated']]);
});

it('refuses source-only integration and health configuration without mutation', function (): void {
    $fixture = new OperationalModule;
    try {
        foreach (['enter', 'diagnostics.read', 'settings.manage'] as $permission) {
            ($this->adminGrant)('runa.admin.'.$permission);
        }
        ($this->adminLogin)();
        ($this->adminProof)();
        $generation = app(ModuleRegistry::class)->snapshot()->generation;
        $this->getJson('/api/admin/operations/capabilities')->assertOk()
            ->assertDontSee('health.configure')->assertDontSee('integrations.manage');
        $this->getJson('/api/admin/operations/module?module='.$fixture->id)->assertOk()
            ->assertJsonMissingPath('data.health_policy')->assertJsonMissingPath('data.health_overrides');
        $this->postJson('/api/admin/operations/health.configure', ['module' => $fixture->id, 'generation' => $generation, 'overrides' => ['mode' => 'scheduled']])->assertUnprocessable();
        $this->getJson('/api/admin/operations/integrations?module='.$fixture->id)->assertUnprocessable();
        $this->postJson('/api/admin/operations/integrations.configure', ['module' => $fixture->id, 'generation' => $generation, 'key' => 'history', 'enabled' => true, 'config' => ['mode' => 'safe']])->assertUnprocessable();
        $this->getJson('/api/admin/operations/settings?module='.$fixture->id)->assertUnprocessable();
        $this->postJson('/api/admin/operations/settings.update', ['module' => $fixture->id, 'generation' => $generation, 'revision' => 0, 'patch' => ['title' => 'Updated']])->assertUnprocessable();
        expect(app(ModuleRegistry::class)->snapshot()->generation)->toBe($generation);
    } finally {
        $fixture->remove();
    }
});

it('binds Optional lifecycle review to actor source generation and native Audit phases', function (): void {
    $fixture = new OperationalModule;
    try {
        foreach (['enter', 'modules.manage'] as $permission) {
            ($this->adminGrant)('runa.admin.'.$permission);
        }
        ($this->adminLogin)();
        ($this->adminProof)();
        $preview = $this->postJson('/api/admin/operations/modules.preview', ['module' => $fixture->id, 'intent' => 'disable'])->assertOk()->assertJsonPath('data.ready', true)->json('data');
        $this->postJson('/api/admin/operations/modules.apply', ['review' => $preview['review'], 'confirmed' => false])->assertUnprocessable();
        DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
        $this->postJson('/api/admin/operations/modules.apply', ['review' => $preview['review'], 'confirmed' => true])->assertConflict();
        $preview = $this->postJson('/api/admin/operations/modules.preview', ['module' => $fixture->id, 'intent' => 'disable'])->assertOk()->json('data');
        DB::statement("ALTER TABLE runa_audit.events ADD CONSTRAINT operational_audit_refusal CHECK (action <> 'module.transition.reserved') NOT VALID");
        try {
            $this->postJson('/api/admin/operations/modules.apply', ['review' => $preview['review'], 'confirmed' => true])->assertConflict();
        } finally {
            DB::statement('ALTER TABLE runa_audit.events DROP CONSTRAINT operational_audit_refusal');
        }
        expect(DB::table('shared.module_transitions')->where('target', $fixture->id)->count())->toBe(0);
        $response = $this->postJson('/api/admin/operations/modules.apply', ['review' => $preview['review'], 'confirmed' => true])->assertOk()->assertJsonPath('data.transition.status', 'succeeded');
        $id = $response->json('data.transition.operationId');
        $this->getJson('/api/admin/operations/modules.status?id='.$id)->assertOk()->assertJsonPath('data.transition.status', 'succeeded');
        expect(app(ModuleRegistry::class)->snapshot()->modules[$fixture->id]->enabled)->toBeFalse()
            ->and(DB::table('runa_audit.events')->where('module', $fixture->id)->whereIn('action', ['module.transition.reserved', 'module.transition.succeeded'])->count())->toBe(2);
        $this->postJson('/api/admin/operations/modules.apply', ['review' => $preview['review'], 'confirmed' => true])->assertConflict();
    } finally {
        $fixture->remove();
    }
});

it('records cooperative cancellation atomically without disclosing payloads or inventing completion', function (): void {
    $fixture = new OperationalModule;
    try {
        foreach (['enter', 'work.read', 'work.cancel', 'health.diagnose'] as $permission) {
            ($this->adminGrant)('runa.admin.'.$permission);
        }
        ($this->adminLogin)();
        ($this->adminProof)();
        $this->postJson('/api/admin/operations/health.diagnose', ['module' => $fixture->id])->assertOk();
        app()->forgetInstance(ModuleWork::class);
        $id = app(ModuleWork::class)->dispatch($fixture->id, 'process', ['private' => 'private-work-payload']);
        $this->getJson('/api/admin/operations/work.status?id='.$id)->assertOk()->assertDontSee('private-work-payload')->assertJsonPath('data.work.cancellationRequested', false);
        DB::statement("ALTER TABLE runa_audit.events ADD CONSTRAINT operational_audit_refusal CHECK (action <> 'work.cancel_requested') NOT VALID");
        try {
            $this->postJson('/api/admin/operations/work.cancel', ['id' => $id])->assertConflict();
        } finally {
            DB::statement('ALTER TABLE runa_audit.events DROP CONSTRAINT operational_audit_refusal');
        }
        expect(DB::table('shared.module_work')->where('public_id', $id)->value('cancel_requested'))->toBeFalse();
        $this->postJson('/api/admin/operations/work.cancel', ['id' => $id])->assertOk()->assertJsonPath('data.work.cancellationRequested', true)->assertJsonPath('data.work.status', 'pending');
        $this->postJson('/api/admin/operations/work.cancel', ['id' => $id])->assertOk();
        expect(DB::table('runa_audit.events')->where('action', 'work.cancel_requested')->count())->toBe(1);
    } finally {
        $fixture->remove();
    }
});

it('retains native waiting and compensation states under lifecycle publication Audit failure', function (): void {
    $fixture = new OperationalModule;
    try {
        foreach (['enter', 'modules.manage'] as $permission) {
            ($this->adminGrant)('runa.admin.'.$permission);
        }
        ($this->adminLogin)();
        ($this->adminProof)();
        TransitionPolicy::$prepare[$fixture->id] = static fn () => new Preparation('waiting');
        $preview = $this->postJson('/api/admin/operations/modules.preview', ['module' => $fixture->id, 'intent' => 'disable'])->assertOk()->json('data');
        $pending = $this->postJson('/api/admin/operations/modules.apply', ['review' => $preview['review'], 'confirmed' => true])->assertOk()->assertJsonPath('data.transition.status', 'pending')->json('data.transition.operationId');
        $this->getJson('/api/admin/operations/modules.status?id='.$pending)->assertOk()->assertJsonPath('data.transition.status', 'pending');
        unset(TransitionPolicy::$prepare[$fixture->id]);
        DB::statement("ALTER TABLE runa_audit.events ADD CONSTRAINT operational_audit_refusal CHECK (action <> 'module.transition.succeeded') NOT VALID");
        try {
            $this->postJson('/api/admin/operations/modules.resume', ['id' => $pending])->assertOk()->assertJsonPath('data.transition.status', 'failed');
        } finally {
            DB::statement('ALTER TABLE runa_audit.events DROP CONSTRAINT operational_audit_refusal');
        }
        expect(app(ModuleRegistry::class)->snapshot()->modules[$fixture->id]->enabled)->toBeTrue()
            ->and(DB::table('runa_audit.events')->where('module', $fixture->id)->where('action', 'module.transition.succeeded')->count())->toBe(0)
            ->and(DB::table('runa_audit.events')->where('module', $fixture->id)->where('action', 'module.transition.failed')->count())->toBe(1);
    } finally {
        $fixture->remove();
    }
});

it('rejects expired foreign altered source and revoked Optional review capabilities', function (): void {
    $fixture = new OperationalModule;
    try {
        foreach (['enter', 'modules.manage'] as $permission) {
            ($this->adminGrant)('runa.admin.'.$permission);
        }
        ($this->adminLogin)();
        ($this->adminProof)();
        $preview = $this->postJson('/api/admin/operations/modules.preview', ['module' => $fixture->id, 'intent' => 'disable'])->assertOk()->json('data');
        $cipher = app(Encrypter::class);
        $sealed = json_decode($cipher->decrypt($preview['review'], false), true, flags: JSON_THROW_ON_ERROR);
        $foreign = $sealed;
        $foreign['actor'] = (string) Str::uuid7();
        $this->postJson('/api/admin/operations/modules.apply', ['review' => $cipher->encrypt(json_encode($foreign, JSON_THROW_ON_ERROR), false), 'confirmed' => true])->assertUnprocessable();
        $expired = $sealed;
        $expired['expires'] = now()->getTimestamp();
        $this->postJson('/api/admin/operations/modules.apply', ['review' => $cipher->encrypt(json_encode($expired, JSON_THROW_ON_ERROR), false), 'confirmed' => true])->assertUnprocessable();
        $this->postJson('/api/admin/operations/modules.apply', ['review' => $preview['review'].'tampered', 'confirmed' => true])->assertConflict();
        $path = $fixture->directory.'/module.json';
        $original = file_get_contents($path);
        $manifest = json_decode($original, true, flags: JSON_THROW_ON_ERROR);
        $manifest['description']['en'] = 'Changed source';
        file_put_contents($path, json_encode($manifest, JSON_THROW_ON_ERROR));
        try {
            $this->postJson('/api/admin/operations/modules.apply', ['review' => $preview['review'], 'confirmed' => true])->assertConflict();
        } finally {
            file_put_contents($path, $original);
        }
        $permission = DB::table('runa_permissions.permissions')->where('code', 'runa.admin.modules.manage')->value('id');
        DB::table('runa_permissions.user_permissions')->where('user_public_id', $this->adminUser->public_id)->where('permission_id', $permission)->delete();
        $this->postJson('/api/admin/operations/modules.apply', ['review' => $preview['review'], 'confirmed' => true])->assertForbidden();
        expect(app(ModuleRegistry::class)->snapshot()->modules[$fixture->id]->enabled)->toBeTrue()
            ->and(DB::table('shared.module_transitions')->where('target', $fixture->id)->count())->toBe(0);
    } finally {
        $fixture->remove();
    }
});

it('rechecks native publication authority after a lifecycle hook and unwinds request authorization', function (): void {
    $fixture = new OperationalModule;
    try {
        foreach (['enter', 'modules.manage'] as $permission) {
            ($this->adminGrant)('runa.admin.'.$permission);
        }
        ($this->adminLogin)();
        ($this->adminProof)();
        $preview = $this->postJson('/api/admin/operations/modules.preview', ['module' => $fixture->id, 'intent' => 'disable'])->assertOk()->json('data');
        TransitionPolicy::$prepare[$fixture->id] = function (): Preparation {
            $permission = DB::table('runa_permissions.permissions')->where('code', 'runa.admin.modules.manage')->value('id');
            DB::table('runa_permissions.user_permissions')->where('user_public_id', $this->adminUser->public_id)->where('permission_id', $permission)->delete();

            return new Preparation('ready');
        };
        $this->postJson('/api/admin/operations/modules.apply', ['review' => $preview['review'], 'confirmed' => true])->assertForbidden();
        expect(app(ModuleRegistry::class)->snapshot()->modules[$fixture->id]->enabled)->toBeTrue()
            ->and(DB::table('runa_audit.events')->where('module', $fixture->id)->where('action', 'module.transition.succeeded')->count())->toBe(0);
        // The trusted CLI scope must not inherit a former HTTP callback/principal.
        app(AdminOperationalAuthority::class)->assertCurrent();
        ($this->adminGrant)('runa.admin.modules.manage');
        unset(TransitionPolicy::$prepare[$fixture->id]);
        $id = DB::table('shared.module_transitions')->where('target', $fixture->id)->value('public_id');
        $this->postJson('/api/admin/operations/modules.resume', ['id' => $id])->assertOk()->assertJsonPath('data.transition.status', 'failed');
        expect(app(ModuleRegistry::class)->snapshot()->modules[$fixture->id]->enabled)->toBeTrue();
    } finally {
        $fixture->remove();
    }
});

it('persists application presentation with owning grants revisions and atomic redacted Audit', function (): void {
    ($this->adminGrant)('runa.admin.enter');
    ($this->adminLogin)();
    ($this->adminProof)();
    $before = DB::table('shared.application_settings')->where('singleton', 1)->first();
    $canonical = $this->getJson('/api/identity')->assertOk()->json('data');
    try {
        $this->getJson('/api/admin/operations/application')->assertOk()->assertJsonPath('data.values', []);
        $this->postJson('/api/admin/operations/application.update', ['revision' => (int) $before->revision, 'patch' => ['presentation.name' => 'Atlas']])->assertForbidden();
        ($this->adminGrant)('runa.admin.application.read');
        expect($this->getJson('/api/admin/operations/application')->assertOk()->json('data.definitions')['presentation.name']['writable'])->toBeFalse();
        ($this->adminGrant)('runa.admin.application.manage');
        $write = fn (array $patch, int $revision) => $this->postJson('/api/admin/operations/application.update', ['revision' => $revision, 'patch' => $patch]);
        $revision = (int) $before->revision;
        $write(['presentation.name' => 'Atlas'], $revision)->assertOk()->assertJsonPath('data.revision', $revision + 1);
        $this->getJson('/api/presentation')->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertJsonPath('data.name', 'Atlas')->assertJsonPath('data.revision', $revision + 1);
        $this->getJson('/api/admin')->assertOk()->assertJsonPath('data.presentation.name', 'Atlas');
        expect(app(MailBranding::class)->name())->toBe('Atlas');
        expect(app(HttpErrorPage::class)->forRequest(404, Request::create('/missing'))['name'])->toBe('Atlas');
        expect($this->getJson('/api/identity')->assertOk()->json('data'))->toBe($canonical);
        $event = DB::table('runa_audit.events')->where('action', 'application.settings.changed')->orderByDesc('id')->first();
        expect($event->actor_public_id)->toBe($this->adminUser->public_id)
            ->and($event->context)->toContain('presentation.name')->not->toContain('Atlas')
            ->and(json_decode($event->changes, true))->toBe([]);
        $write(['presentation.name' => 'Stale'], $revision)->assertConflict();
        $write(['APP_KEY' => 'forbidden'], $revision + 1)->assertForbidden();
        $write(['presentation.name' => 12], $revision + 1)->assertUnprocessable();
        $write(['presentation.logo' => 'https://invalid.test/logo.png'], $revision + 1)->assertUnprocessable();
        $this->postJson('/api/admin/operations/application.update', ['revision' => $revision + 1, 'patch' => ['presentation.name' => 'Forged'], 'actor' => $this->adminUser->public_id])->assertUnprocessable();
        DB::statement("ALTER TABLE runa_audit.events ADD CONSTRAINT application_settings_audit_refusal CHECK (action <> 'application.settings.changed') NOT VALID");
        try {
            $write(['presentation.name' => 'Refused'], $revision + 1)->assertServiceUnavailable()->assertJsonPath('reason', 'application_settings_audit_unavailable');
        } finally {
            DB::statement('ALTER TABLE runa_audit.events DROP CONSTRAINT application_settings_audit_refusal');
        }
        $this->getJson('/api/presentation')->assertOk()->assertJsonPath('data.name', 'Atlas')->assertJsonPath('data.revision', $revision + 1);
        $write(['presentation.name' => null], $revision + 1)->assertOk();
        $this->getJson('/api/presentation')->assertOk()->assertJsonPath('data.name', $canonical['applicationName']);
        $permission = DB::table('runa_permissions.permissions')->where('code', 'runa.admin.application.manage')->value('id');
        DB::table('runa_permissions.user_permissions')->where('permission_id', $permission)->delete();
        $write(['presentation.name' => 'Revoked'], $revision + 2)->assertForbidden();
    } finally {
        DB::table('shared.application_settings')->where('singleton', 1)->update(['values' => $before->values, 'revision' => $before->revision, 'updated_at' => $before->updated_at]);
    }
});

it('falls back publicly on missing application storage while protected settings fail closed without creating it', function (): void {
    ($this->adminGrant)('runa.admin.enter');
    ($this->adminGrant)('runa.admin.application.read');
    ($this->adminLogin)();
    ($this->adminProof)();
    $name = app(ApplicationIdentity::class)->applicationName;
    DB::statement('ALTER TABLE shared.application_settings RENAME TO application_settings_point_ten_unavailable');
    try {
        $this->getJson('/api/presentation')->assertOk()->assertJsonPath('data.name', $name)->assertJsonPath('data.revision', 0);
        $this->getJson('/api/admin/operations/application')->assertServiceUnavailable();
        expect(app(MailBranding::class)->name())->toBe($name);
        expect(app(HttpErrorPage::class)->forRequest(503, Request::create('/missing'))['name'])->toBe($name);
        expect(DB::selectOne("SELECT to_regclass('shared.application_settings') AS name")->name)->toBeNull();
    } finally {
        DB::statement('ALTER TABLE shared.application_settings_point_ten_unavailable RENAME TO application_settings');
    }
});

it('accepts only App owned frozen setting catalogues and source logo keys with safe fallbacks', function (): void {
    $directory = app_path('Settings/PointTenFixture');
    $asset = public_path('__point_ten_logo.png');
    if (file_exists($directory) || file_exists($asset)) {
        throw new RuntimeException('Application settings fixture refuses existing source or asset.');
    }
    File::ensureDirectoryExists($directory);
    file_put_contents($directory.'/Settings.php', <<<'SOURCE'
<?php
namespace App\Settings\PointTenFixture;
final class Settings implements \Runa\Contracts\Settings\ApplicationSettingProvider
{
    public function settings(): array
    {
        return [
            new \Runa\Contracts\Settings\ApplicationSetting('business.label', 'string', 'Default business label', 'Business label', 'runa.admin.application.read', 'runa.admin.application.manage', 100),
            new \Runa\Contracts\Settings\ApplicationSetting('business.hidden', 'string', 'Private default', 'Hidden label', 'runa.auth.profile.read', 'runa.auth.profile.edit', 100),
        ];
    }
    public function logos(): array
    {
        return [new \Runa\Contracts\Settings\ApplicationLogo('atlas', '/__point_ten_logo.png', 'Atlas logo')];
    }
}
SOURCE);
    require_once $directory.'/Settings.php';
    copy(base_path('runa/resources/images/runa-mail-logo.png'), $asset);
    $before = DB::table('shared.application_settings')->where('singleton', 1)->first();
    try {
        $registry = new SettingRegistry(app(ApplicationManagement::class));
        $provider = new Settings;
        $registry->register($provider);
        expect(fn () => $registry->register($provider))->toThrow(InvalidArgumentException::class);
        app()->instance(SettingRegistry::class, $registry);
        $registry->freeze();
        expect(fn () => $registry->register($provider))->toThrow(InvalidArgumentException::class);
        ($this->adminGrant)('runa.admin.enter');
        ($this->adminGrant)('runa.admin.application.read');
        ($this->adminGrant)('runa.admin.application.manage');
        ($this->adminLogin)();
        ($this->adminProof)();
        $read = $this->getJson('/api/admin/operations/application')->assertOk();
        expect($read->json('data.values'))->toHaveKey('business.label')->not->toHaveKey('business.hidden');
        expect($read->json('data.definitions'))->not->toHaveKey('business.hidden');
        $revision = (int) $before->revision;
        $this->postJson('/api/admin/operations/application.update', ['revision' => $revision, 'patch' => ['presentation.logo' => 'atlas', 'business.label' => 'Published business label']])->assertOk();
        $this->getJson('/api/presentation')->assertOk()->assertJsonPath('data.logo.src', '/__point_ten_logo.png')->assertJsonMissing(['business.label' => 'Published business label']);
        expect(app(MailBranding::class)->logo())->toStartWith('cid:');
        expect(app(HttpErrorPage::class)->forRequest(404, Request::create('/missing'))['logo']['src'])->toBe('/__point_ten_logo.png');
        // A new source catalogue can remove an asset without deleting persisted selections.
        $replacement = new SettingRegistry(app(ApplicationManagement::class));
        app()->instance(SettingRegistry::class, $replacement);
        $this->getJson('/api/presentation')->assertOk()->assertJsonPath('data.logo', app(ApplicationIdentity::class)->logo);
        expect(DB::table('shared.application_settings')->where('singleton', 1)->value('values'))->toContain('atlas', 'Published business label');
    } finally {
        DB::table('shared.application_settings')->where('singleton', 1)->update(['values' => $before->values, 'revision' => $before->revision, 'updated_at' => $before->updated_at]);
        File::deleteDirectory($directory);
        unlink($asset);
    }
});

it('module operation history scopes kinds and modules, paginates safely and never selects private payloads', function (): void {
    ($this->adminGrant)('runa.admin.enter');
    ($this->adminLogin)();
    ($this->adminProof)();
    $this->getJson('/api/admin/operations/history?module=runa.auth')->assertForbidden();
    $insert = function (string $intent, string $target, array $affected, string $status): string {
        $id = (string) Str::uuid7();
        DB::table('shared.module_transitions')->insert(['public_id' => $id, 'intent' => $intent, 'target' => $target,
            'affected' => json_encode($affected, JSON_THROW_ON_ERROR), 'generation' => 1,
            'payload' => 'PRIVATE_RECOVERY_SNAPSHOT_NOT_TO_BE_DECRYPTED', 'status' => $status, 'created_at' => now(), 'updated_at' => now()]);

        return $id;
    };
    $one = $insert('enable', 'runa.auth', ['runa.auth'], 'succeeded');
    $two = $insert('disable', 'runa.audit', ['runa.audit', 'runa.auth'], 'failed');
    $settings = $insert('settings', 'runa.auth', ['runa.auth'], 'succeeded');
    $unrelated = $insert('enable', 'runa.audit', ['runa.audit'], 'refused');
    ($this->adminGrant)('runa.admin.modules.manage');
    $response = $this->getJson('/api/admin/operations/history?module=runa.auth&limit=1')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    $ids = [$one, $two];
    rsort($ids);
    expect($response->json('data.operations'))->toHaveCount(1)
        ->and($response->json('data.operations.0.public_id'))->toBe($ids[0])
        ->and($response->json('data.operations.0.actions'))->toBe(['modules.status'])
        ->and($response->getContent())->not->toContain('PRIVATE_RECOVERY', 'payload', 'generation', 'publication_generation', 'settings_values');
    $next = $this->getJson('/api/admin/operations/history?'.http_build_query(['module' => 'runa.auth', 'limit' => 1, 'cursor' => $response->json('data.next_cursor')]))->assertOk();
    expect($next->json('data.operations.0.public_id'))->toBe($ids[1])->and($next->json('data.next_cursor'))->toBeNull();
    ($this->adminGrant)('runa.admin.settings.manage');
    $all = $this->getJson('/api/admin/operations/history?module=runa.auth')->assertOk()->json('data.operations');
    expect(array_column($all, 'public_id'))->toContain($one, $two, $settings)->not->toContain($unrelated);
    $this->getJson('/api/admin/operations/history?module=runa.auth&limit=101')->assertUnprocessable();
    $this->getJson('/api/admin/operations/history?module=runa.auth&cursor=invalid')->assertUnprocessable();
    $this->getJson('/api/admin/operations/history?module=runa.auth&actor=forged')->assertUnprocessable();
    $this->getJson('/api/admin/operations/history?module=app.unknown')->assertNotFound();
});

it('module operation history settings authority does not reveal lifecycle changes', function (): void {
    foreach (['runa.admin.enter', 'runa.admin.settings.manage'] as $permission) {
        ($this->adminGrant)($permission);
    }
    ($this->adminLogin)();
    ($this->adminProof)();
    $inserted = [];
    foreach (['enable', 'settings'] as $intent) {
        $inserted[$intent] = (string) Str::uuid7();
        DB::table('shared.module_transitions')->insert(['public_id' => $inserted[$intent], 'intent' => $intent, 'target' => 'runa.auth', 'affected' => '["runa.auth"]', 'generation' => 1, 'payload' => 'PRIVATE', 'status' => 'succeeded', 'created_at' => now(), 'updated_at' => now()]);
    }
    $rows = $this->getJson('/api/admin/operations/history?module=runa.auth')->assertOk()->json('data.operations');
    expect(array_values(array_unique(array_column($rows, 'intent'))))->toBe(['settings'])
        ->and(array_column($rows, 'public_id'))->toContain($inserted['settings'])->not->toContain($inserted['enable']);
    foreach ($rows as $row) {
        expect($row['actions'])->toBe(['settings.status']);
    }
});

it('module operation history advertises recovery only for active native states', function (): void {
    $id = (string) Str::uuid7();
    DB::table('shared.module_transitions')->insert(['public_id' => $id, 'intent' => 'settings', 'target' => 'runa.auth', 'affected' => '["runa.auth"]', 'generation' => 1, 'payload' => 'PRIVATE', 'status' => 'recovery_required', 'created_at' => now(), 'updated_at' => now()]);
    $history = app(ModuleOperationHistory::class);
    expect($history->read('runa.auth', ['settings'])['operations'][0]['actions'])->toBe(['settings.status', 'settings.recover']);
    DB::table('shared.module_transitions')->where('public_id', $id)->update(['intent' => 'disable']);
    expect($history->read('runa.auth', ['disable'])['operations'][0]['actions'])->toBe(['modules.status', 'modules.resume', 'modules.cancel']);
    DB::table('shared.module_transitions')->where('public_id', $id)->update(['status' => 'failed']);
    expect($history->read('runa.auth', ['disable'])['operations'][0]['actions'])->toBe(['modules.status']);
});

it('reads bounded task history including terminal work without exposing private payloads', function (): void {
    $fixture = new OperationalModule;
    try {
        foreach (['enter', 'health.diagnose'] as $permission) {
            ($this->adminGrant)('runa.admin.'.$permission);
        }
        ($this->adminLogin)();
        ($this->adminProof)();
        $this->postJson('/api/admin/operations/health.diagnose', ['module' => $fixture->id])->assertOk();
        app()->forgetInstance(ModuleWork::class);
        $work = app(ModuleWork::class);
        $active = $work->dispatch($fixture->id, 'process', ['private' => 'task-secret']);
        $terminal = $work->dispatch($fixture->id, 'process', ['private' => 'task-secret']);
        DB::table('shared.module_work')->where('public_id', $terminal)->update(['status' => 'succeeded', 'payload' => 'not-decryptable-private-task']);
        $url = '/api/admin/operations/work?module='.$fixture->id;
        $this->getJson($url)->assertForbidden();
        ($this->adminGrant)('runa.admin.work.read');
        $this->getJson($url.'&limit=1')->assertOk()->assertJsonPath('data.work.0.workId', $terminal)->assertJsonPath('data.work.0.status', 'succeeded')->assertJsonPath('data.truncated', true)->assertDontSee('task-secret')->assertDontSee('not-decryptable-private-task');
        $result = $this->getJson($url.'&limit=2')->assertOk()->assertJsonPath('data.truncated', false)->json('data.work');
        expect(array_column($result, 'workId'))->toBe([$terminal, $active]);
        expect(array_map(fn ($row) => $row->workId, $work->active($fixture->id)))->toBe([$active]);
        expect($work->history('runa.auth'))->toBe([]);
        $this->getJson($url.'&limit=1001')->assertUnprocessable();
        expect(fn () => $work->history($fixture->id, 0))->toThrow(RuntimeException::class);
    } finally {
        $fixture->remove();
    }
});

it('publishes display defaults and uploaded logo icons atomically with revision grants and Audit', function (): void {
    foreach (['enter', 'application.read', 'application.manage'] as $permission) {
        ($this->adminGrant)('runa.admin.'.$permission);
    }
    ($this->adminLogin)();
    ($this->adminProof)();
    $before = DB::table('shared.application_settings')->where('singleton', 1)->first();
    $png = static function (int $width, int $height, string $pixel): string {
        $chunk = static fn (string $type, string $data): string => pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));

        return "\x89PNG\r\n\x1a\n".$chunk('IHDR', pack('NNCCCCC', $width, $height, 8, 6, 0, 0, 0)).$chunk('IDAT', gzcompress(str_repeat("\0".str_repeat($pixel, $width), $height))).$chunk('IEND', '');
    };
    $logo = $png(80, 40, "\xff\0\0\xff");
    $icon = $png(64, 64, "\xff\0\0\xff");
    $custom = $png(64, 64, "\0\0\xff\xff");
    $revision = (int) $before->revision;
    $request = ['revision' => $revision, 'patch' => ['presentation.logo' => 'uploaded', 'presentation.locale' => 'en', 'presentation.theme' => 'dark', 'presentation.timezone' => 'America/New_York'], 'images' => ['logo' => base64_encode($logo), 'logo_icon' => base64_encode($icon)]];
    try {
        $this->postJson('/api/admin/operations/application.update', $request)->assertOk()->assertJsonPath('data.revision', ++$revision);
        $presentation = $this->getJson('/api/presentation')->assertOk()->assertJsonPath('data.defaults.locale', 'en')->assertJsonPath('data.defaults.theme', 'dark')->assertJsonPath('data.defaults.timezone', 'America/New_York')->json('data');
        $this->get($presentation['logo']['src'])->assertOk()->assertHeader('Content-Type', 'image/png')->assertHeader('X-Content-Type-Options', 'nosniff')->assertContent($logo);
        $this->get($presentation['favicon'])->assertOk()->assertContent($icon);
        $this->get('/api/presentation/assets/logo/'.str_repeat('0', 64).'.png')->assertNotFound();
        expect(app(MailBranding::class)->logo())->toStartWith('cid:');
        expect(app(HttpErrorPage::class)->forRequest(503, Request::create('/maintenance'))['logo'])->toBe(app(ApplicationIdentity::class)->logo);
        $message = new Email;
        $message->html('<img src="'.app(MailBranding::class)->logo().'">');
        app(MailBranding::class)->attachLogo(new MessageSending($message));
        expect($message->getAttachments())->toHaveCount(1);
        expect($message->getAttachments()[0]->getBody())->toBe($logo);
        $this->postJson('/api/admin/operations/application.update', $request)->assertConflict();
        $this->postJson('/api/admin/operations/application.update', ['revision' => $revision, 'patch' => ['presentation.timezone' => 'invalid/timezone']])->assertUnprocessable();
        DB::statement("ALTER TABLE runa_audit.events ADD CONSTRAINT branding_audit_refusal CHECK (action <> 'application.settings.changed') NOT VALID");
        try {
            $this->postJson('/api/admin/operations/application.update', ['revision' => $revision, 'patch' => ['presentation.favicon' => 'uploaded'], 'images' => ['favicon' => base64_encode($custom)]])->assertServiceUnavailable();
        } finally {
            DB::statement('ALTER TABLE runa_audit.events DROP CONSTRAINT branding_audit_refusal');
        }
        $this->getJson('/api/presentation')->assertOk()->assertJsonPath('data.favicon', $presentation['favicon'])->assertJsonPath('data.revision', $revision);
        $this->postJson('/api/admin/operations/application.update', ['revision' => $revision, 'patch' => ['presentation.favicon' => 'uploaded'], 'images' => ['favicon' => base64_encode($custom)]])->assertOk();
        $revision++;
        $customUrl = $this->getJson('/api/presentation')->assertOk()->json('data.favicon');
        $this->get($customUrl)->assertOk()->assertContent($custom);
        $this->postJson('/api/admin/operations/application.update', ['revision' => $revision, 'patch' => ['presentation.favicon' => null]])->assertOk();
        $revision++;
        $this->getJson('/api/presentation')->assertOk()->assertJsonPath('data.favicon', $presentation['favicon']);
        $this->get($customUrl)->assertNotFound();
        $this->postJson('/api/admin/operations/application.update', ['revision' => $revision, 'patch' => ['presentation.logo' => null, 'presentation.locale' => null, 'presentation.theme' => null, 'presentation.timezone' => null]])->assertOk();
        $revision++;
        $this->getJson('/api/presentation')->assertOk()->assertJsonPath('data.favicon', null)->assertJsonPath('data.defaults.locale', 'pl')->assertJsonPath('data.defaults.theme', 'system')->assertJsonPath('data.defaults.timezone', 'Europe/Warsaw');
        $this->get($presentation['logo']['src'])->assertNotFound();
        $this->postJson('/api/admin/operations/application.update', ['revision' => $revision, 'patch' => ['presentation.logo' => 'uploaded']])->assertUnprocessable();
        $this->postJson('/api/admin/operations/application.update', ['revision' => $revision, 'patch' => ['presentation.logo' => 'uploaded'], 'images' => ['logo' => base64_encode($logo), 'logo_icon' => base64_encode($logo)]])->assertUnprocessable();
        $this->postJson('/api/admin/operations/application.update', ['revision' => $revision, 'patch' => ['presentation.favicon' => 'uploaded'], 'images' => ['favicon' => base64_encode($custom.'<script>')]])->assertUnprocessable();
        expect(DB::table('runa_audit.events')->where('action', 'application.settings.changed')->latest('id')->value('context'))->not->toContain(base64_encode($logo));
    } finally {
        DB::table('shared.application_settings')->where('singleton', 1)->update(['values' => $before->values, 'revision' => $before->revision, 'updated_at' => $before->updated_at, 'branding_logo' => null, 'branding_logo_icon' => null, 'branding_favicon' => null]);
    }
});

it('uses Auth protected section idle access for Admin and never trusts activity request policy', function (): void {
    CarbonImmutable::setTestNow('2026-10-31 12:00:00 UTC');
    ($this->adminGrant)('runa.admin.enter');
    ($this->adminLogin)();
    $this->postJson('/api/admin/activity')->assertForbidden();
    ($this->adminProof)();
    CarbonImmutable::setTestNow('2026-10-31 12:04:00 UTC');
    $this->getJson('/api/admin/activity')->assertOk()->assertJsonPath('data.remaining_seconds', 60);
    $this->postJson('/api/admin/activity', ['action' => 'app.unrelated', 'ttlSeconds' => 900, 'requirement' => 'none'])
        ->assertOk()->assertJsonPath('data.remaining_seconds', 300);
    CarbonImmutable::setTestNow('2026-10-31 12:05:01 UTC');
    $this->getJson('/api/admin')->assertOk()->assertJsonPath('data.activity.remaining_seconds', 239);
    CarbonImmutable::setTestNow('2026-10-31 12:09:00 UTC');
    $this->getJson('/api/admin/activity')->assertForbidden()->assertJsonPath('reason', 'step_up_required');
    $this->postJson('/api/admin/activity')->assertForbidden()->assertJsonPath('reason', 'step_up_required');
});

it('Auth user administration hides disabled feature metadata and refuses hidden commands', function (): void {
    $settings = app(ModuleSettings::class)->read('runa.auth');
    app(ModuleSettings::class)->update('runa.auth', ['totp_enabled' => false, 'email_otp_enabled' => false, 'avatar_editor' => 'user', 'password_expiry_days' => 0], $settings['revision'], $settings['generation']);
    authAdministrationLogin($this, ['runa.auth.users.read', 'runa.auth.users.manage', 'runa.auth.profiles.read', 'runa.auth.emails.read', 'runa.auth.emails.reverify', 'runa.auth.emails.verify_bypass', 'runa.auth.secondary.read', 'runa.auth.trust.manage', 'runa.auth.two_factor.reset', 'runa.auth.passwords.reset', 'runa.auth.policy.exceptions']);
    $page = $this->getJson('/api/admin/runa.auth/users')->assertOk()->json('data');
    $sections = collect($page['sections'])->keyBy('key');
    expect($sections->has('secondary'))->toBeFalse()->and($sections->has('trust'))->toBeFalse()->and($sections->has('avatar'))->toBeFalse();
    $fields = collect($sections['lifecycle']['fields'])->pluck('key')->all();
    expect($fields)->not->toContain('password_expires_at', 'password_change_required', 'totp_enrolled', 'email_factor_enrolled', 'recovery_codes_remaining');
    expect(collect($sections['primary_email']['fields'])->pluck('key')->all())->not->toContain('verified', 'verification_required', 'replacement_email');
    expect(collect($sections['primary_email']['commands'])->pluck('key')->all())->not->toContain('replace', 'require_verification');
    expect(collect($sections['security']['commands'])->pluck('key')->all())->not->toContain('factor_reset');
    expect(collect($sections['lifecycle']['fields'])->firstWhere('key', 'operation')['choices'])->not->toContain('require_password_change');
    $this->postJson('/api/admin/runa.auth/users/sections/primary_email/commands', ['command' => 'require_verification', 'public_id' => $this->adminUser->public_id, 'revision' => '1', 'values' => [], 'confirmed' => true])->assertUnprocessable();
    $bootstrap = $this->getJson('/api/admin')->assertOk()->json('data');
    expect(json_encode($bootstrap))->not->toContain('/runa.auth/bulk_sessions');
});

it('Auth user administration reports native live sessions and authorized account dates', function (): void {
    $identity = app(ChangeIdentity::class)->create(new NewIdentity('status-target@example.test', name: 'Status target', active: true));
    $target = User::where('public_id', $identity->publicId)->firstOrFail();
    DB::transaction(function () use ($target): void {
        app(SessionStore::class)->admit(DB::connection(), $target, 'fixture-live-session', false);
        app(SessionStore::class)->admit(DB::connection(), $target, 'fixture-expired-session', false);
        DB::table('runa_auth.sessions')->where('session_hash', hash('sha256', 'fixture-expired-session'))->update(['authenticated_at' => now()->subDays(2), 'activity_at' => now()->subDays(2)]);
    });
    authAdministrationLogin($this, ['runa.auth.users.read', 'runa.auth.profiles.read', 'runa.auth.emails.read', 'runa.auth.sessions.manage', 'runa.auth.avatars.manage']);
    $avatar = authAdministrationRead($this, 'avatar', $target->public_id);
    expect($avatar['values']['has_avatar'])->toBeFalse()->and($avatar['values']['avatar_max_kib'])->toBe(2048)->and($avatar['values']['avatar_upload_pending'])->toBeFalse();
    $native = app(PrincipalScope::class)->run(new TrustedPrincipal($this->adminUser->public_id), fn () => app(ModuleApis::class)->resolve('runa.auth', AuthAdministration::class, '2.1.0')->users(new UserDirectoryQuery(search: 'Status target')));
    expect($native->items[0]->details['session_count'])->toBe(1);
    $read = fn () => authAdministrationRead($this, 'directory', query: ['kind' => 'page', 'filters' => json_encode(['display_name' => 'Status target'])])['items'][0]['values'];
    $values = $read();
    expect($values['active'])->toBeTrue()->and($values['logged_in'])->toBeTrue()->and($values['session_count'])->toBe(1)
        ->and($values['email'])->toBe('status-target@example.test')->and($values['created_at'])->toBeString()->and($values['updated_at'])->toBeString()
        ->and($values['authenticated_at'])->toBeString()->and($values['activity_at'])->toBeString()
        ->and($values)->not->toHaveKey('verified')->not->toHaveKey('email_verified_at');
    DB::table('runa_auth.sessions')->where('user_id', $target->id)->update(['revoked_at' => now()]);
    expect($read()['logged_in'])->toBeFalse()->and($read()['session_count'])->toBe(0);
});

it('Auth user administration filters all authorized facts before pagination and binds cursors', function (): void {
    expect(fn () => new ManagementRead(filters: array_fill_keys(array_map(fn ($i) => 'field'.$i, range(1, 65)), true)))->toThrow(InvalidArgumentException::class);
    $settings = app(ModuleSettings::class)->read('runa.auth');
    app(ModuleSettings::class)->update('runa.auth', ['email_verification' => 'new', 'email_otp_enabled' => true, 'password_expiry_days' => 30, 'trusted_devices_enabled' => true], $settings['revision'], $settings['generation']);
    DB::table('runa_auth.users')->where('id', $this->adminUser->id)->update(['password_changed_at' => now()]);
    $target = app(ChangeIdentity::class)->create(new NewIdentity('filter-facts@example.test', firstName: 'Anna', surname: 'Test', active: true));
    DB::table('runa_auth.users')->where('public_id', $target->publicId)->update(['email_verified_at' => now(), 'password' => Hash::make('test-password'), 'password_changed_at' => now()->subDays(40), 'totp_secret' => app(Encrypter::class)->encrypt('test-secret'), 'recovery_hashes' => json_encode(['one', 'two'])]);
    authAdministrationLogin($this, ['runa.auth.users.read', 'runa.auth.profiles.read', 'runa.auth.emails.read', 'runa.auth.sessions.manage', 'runa.auth.passwords.reset', 'runa.auth.two_factor.reset', 'runa.auth.invitations.manage', 'runa.auth.trust.manage', 'runa.auth.avatars.manage']);
    $filters = ['display_name' => 'Anna Test', 'email' => 'filter-facts@', 'first_name' => 'Anna', 'surname' => 'Test', 'active' => true, 'logged_in' => false, 'session_count' => 0, 'verified' => true, 'verification_required' => true, 'two_factor_enabled' => true, 'two_factor_required' => true, 'totp_enrolled' => true, 'email_factor_enrolled' => false, 'recovery_codes_remaining' => 2, 'password_set' => true, 'password_expired' => true, 'invitation_pending' => false, 'trusted_device_count' => 0, 'has_avatar' => false, 'operator_recovery_pending' => false, 'created_at' => json_encode(['from' => now()->subDay()->utc()->format('Y-m-d\TH:i:s.000\Z'), 'until' => now()->addDay()->utc()->format('Y-m-d\TH:i:s\Z')])];
    foreach ($filters as $key => $value) {
        $response = $this->getJson('/api/admin/runa.auth/users/sections/directory?'.http_build_query(['kind' => 'page', 'filters' => json_encode([$key => $value])]));
        $this->assertSame(200, $response->status(), $key.': '.($response->json('error.code') ?? 'validation failure'));
    }
    $result = authAdministrationRead($this, 'directory', query: ['kind' => 'page', 'filters' => json_encode($filters), 'limit' => 1]);
    expect($result['total'])->toBe(1)->and($result['items'][0]['public_id'])->toBe($target->publicId);
    $facts = $result['items'][0]['values'];
    expect($facts['two_factor_enabled'])->toBeTrue()->and($facts['password_expired'])->toBeTrue()->and($facts['recovery_codes_remaining'])->toBe(2)->and($facts['password_expires_at'])->toBeString();
    expect(json_encode($facts))->not->toContain('test-secret', 'test-password', 'recovery_hashes', 'totp_secret');
    $filters['two_factor_enabled'] = false;
    expect(authAdministrationRead($this, 'directory', query: ['kind' => 'page', 'filters' => json_encode($filters)])['total'])->toBe(0);
    $first = authAdministrationRead($this, 'directory', query: ['kind' => 'page', 'limit' => 1]);
    $this->getJson('/api/admin/runa.auth/users/sections/directory?'.http_build_query(['kind' => 'page', 'cursor' => $first['next'], 'filters' => json_encode(['active' => true])]))->assertUnprocessable();
    foreach ([['created_at' => json_encode(['from' => '2026-02-30T00:00:00Z'])], ['password' => true], ['session_count' => -1]] as $invalid) {
        $this->getJson('/api/admin/runa.auth/users/sections/directory?'.http_build_query(['kind' => 'page', 'filters' => json_encode($invalid)]))->assertUnprocessable();
    }
});

it('Auth user administration executes each native bulk operation with its declared proof', function (string $operation): void {
    $settings = app(ModuleSettings::class)->read('runa.auth');
    app(ModuleSettings::class)->update('runa.auth', ['email_verification' => 'new', 'password_expiry_days' => 30, 'trusted_devices_enabled' => true], $settings['revision'], $settings['generation']);
    DB::table('runa_auth.users')->where('id', $this->adminUser->id)->update(['password_changed_at' => now()]);
    $ids = [];
    foreach (['one', 'two'] as $suffix) {
        $target = app(ChangeIdentity::class)->create(new NewIdentity('bulk-'.$suffix.'@example.test', name: 'Bulk '.$suffix, active: true));
        $ids[] = $target->publicId;
        $values = ['email_verified_at' => now()];
        if ($operation === 'password_reset') {
            $values += ['password' => Hash::make('target-password'), 'password_changed_at' => now()];
        }
        if ($operation === 'activate') {
            $values['active'] = false;
        }
        if ($operation === 'restore') {
            $values += ['deleted_at' => now(), 'active' => false];
        }
        if ($operation === 'factor_reset') {
            $values += ['totp_secret' => app(Encrypter::class)->encrypt('bulk-totp-secret'), 'email_factor' => true, 'recovery_hashes' => json_encode(['one', 'two'])];
        }
        if ($operation === 'remove_avatar') {
            $values += ['avatar_path' => 'avatars/'.$suffix.'.png', 'avatar_type' => 'image/png'];
        }
        DB::table('runa_auth.users')->where('public_id', $target->publicId)->update($values);
        $user = User::withTrashed()->where('public_id', $target->publicId)->firstOrFail();
        if (in_array($operation, ['deactivate', 'reset_preferences'], true)) {
            DB::transaction(fn () => app(SessionStore::class)->admit(DB::connection(), $user, 'bulk-impact-'.$suffix, false));
        }
        if ($operation === 'invalidate_invitation') {
            expect(app(EntryTokens::class)->invite($target->publicId, 1))->toBeTrue();
        }
        if ($operation === 'revoke_trust') {
            DB::table('runa_auth.trusted_devices')->insert(['public_id' => (string) Str::uuid7(), 'user_id' => $user->id, 'token_hash' => hash('sha256', 'bulk-trust-'.$suffix), 'security_version' => 1, 'factor_method' => 'totp', 'created_at' => now(), 'expires_at' => now()->addDay()]);
        }
        if ($operation === 'remove_avatar') {
            DB::table('runa_auth.avatar_files')->insert(['user_id' => $user->id, 'path' => 'avatars/'.$suffix.'.png', 'available_at' => now()->addDay(), 'created_at' => now()]);
        }
        if ($operation === 'reset_preferences') {
            DB::table('runa_auth.preferences')->insert(['user_id' => $user->id, 'scope' => 'runa:runa.auth:global:locale', 'module' => 'runa.auth', 'value' => '"en"', 'definition_version' => 1, 'created_at' => now(), 'updated_at' => now()]);
        }
        if ($operation === 'revoke_exceptions') {
            DB::table('runa_auth.policy_exceptions')->insert(['public_id' => (string) Str::uuid7(), 'user_id' => $user->id, 'key' => 'session_idle_minutes', 'value' => 30, 'reason' => 'Bulk fixture', 'actor' => $this->adminUser->public_id, 'policy_revision' => app(ModuleSettings::class)->read('runa.auth')['revision'], 'version' => 1, 'expires_at' => now()->addDay(), 'created_at' => now(), 'updated_at' => now()]);
        }
    }
    $access = BulkUserOperations::access($operation);
    authAdministrationLogin($this, ['runa.auth.users.read', 'runa.auth.users.deleted.read', 'runa.permissions.users.select', 'runa.auth.'.$access['permission']]);
    $record = authAdministrationRead($this, 'bulk_users');
    expect($record['public_id'])->toBeNull();
    $values = ['public_ids' => $ids];
    if (in_array($operation, ['schedule_activation', 'schedule_deactivation', 'set_attempt_lock'], true)) {
        $values['at'] = now()->addDay()->utc()->format('Y-m-d\TH:i:s.000\Z');
    }
    if ($operation === 'set_security_lock') {
        $values['enabled'] = true;
    }
    $input = ['command' => $operation, 'public_id' => null, 'revision' => '0', 'values' => $values, 'confirmed' => true];
    $input['review'] = $this->postJson('/api/admin/runa.auth/users/sections/bulk_users/preview', $input)->assertOk()->json('data');
    expect($input['review']['changes']['summary']['accounts'])->toBe(2);
    if ($operation === 'deactivate') {
        expect($input['review']['changes']['summary']['sessions'])->toBe(2);
    }
    if ($operation === 'reset_preferences') {
        expect($input['review']['changes']['summary'])->not->toHaveKey('sessions')->not->toHaveKey('trusted_devices');
    }
    $protected = in_array($operation, ['delete', 'restore', 'password_reset', 'factor_reset', 'revoke_exceptions'], true);
    expect($input['review']['requirement'])->toBe($protected ? 'password' : 'none');
    if ($protected) {
        $this->postJson('/api/admin/runa.auth/users/sections/bulk_users/commands', $input)->assertForbidden()->assertJsonPath('error.code', 'step_up_required');
        $this->postJson('/api/runa/auth/step-up', ['action' => 'runa.auth.users.bulk.'.$operation, 'current_password' => 'admin-entry-password'])->assertOk();
    }
    $this->postJson('/api/admin/runa.auth/users/sections/bulk_users/commands', $input)->assertOk()->assertJsonPath('data.status', 'committed');
    expect(DB::table('runa_auth.step_up_proofs')->where('action', 'runa.auth.users.bulk.'.$operation)->whereNotNull('consumed_at')->count())->toBe($protected ? 1 : 0);
    $users = User::withTrashed()->whereIn('public_id', $ids)->get();
    foreach ($users as $user) {
        match ($operation) {
            'activate' => expect($user->active)->toBeTrue(),
            'deactivate' => expect($user->active)->toBeFalse(),
            'delete' => expect($user->trashed())->toBeTrue(),
            'restore' => expect($user->trashed())->toBeFalse()->and($user->active)->toBeFalse(),
            'set_security_lock' => expect($user->security_locked)->toBeTrue(),
            'require_password_change' => expect($user->password_change_required)->toBeTrue(),
            'schedule_activation' => expect($user->activate_at)->not->toBeNull(),
            'schedule_deactivation' => expect($user->deactivate_at)->not->toBeNull(),
            'set_attempt_lock' => expect($user->attempt_locked_until)->not->toBeNull(),
            'require_verification' => expect($user->email_verified_at)->toBeNull()->and($user->verification_forced)->toBeTrue(),
            'invite','password_reset' => expect(DB::table('runa_auth.entry_tokens')->where('user_id', $user->id)->where('purpose', $operation === 'invite' ? 'invitation' : 'reset')->whereNull('invalidated_at')->count())->toBe(1),
            'reset_preferences' => expect($user->preference_revision)->toBe(1)->and(DB::table('runa_auth.preferences')->where('user_id', $user->id)->count())->toBe(0),
            'factor_reset' => expect($user->security_version)->toBe(2)->and($user->email_factor)->toBeFalse(),
            'invalidate_invitation' => expect(DB::table('runa_auth.entry_tokens')->where('user_id', $user->id)->where('purpose', 'invitation')->whereNull('invalidated_at')->count())->toBe(0),
            'revoke_trust' => expect(DB::table('runa_auth.trusted_devices')->where('user_id', $user->id)->whereNull('revoked_at')->count())->toBe(0),
            'remove_avatar' => expect($user->avatar_path)->toBeNull()->and(CarbonImmutable::parse(DB::table('runa_auth.avatar_files')->where('user_id', $user->id)->value('available_at'))->lte(now()))->toBeTrue(),
            'revoke_exceptions' => expect(DB::table('runa_auth.policy_exceptions')->where('user_id', $user->id)->whereNull('revoked_at')->count())->toBe(0),
            default => throw new LogicException('Missing bulk expectation.'),
        };
    }
})->with([['activate'], ['deactivate'], ['delete'], ['restore'], ['schedule_activation'], ['schedule_deactivation'], ['set_attempt_lock'], ['set_security_lock'], ['require_password_change'], ['invite'], ['invalidate_invitation'], ['require_verification'], ['password_reset'], ['factor_reset'], ['revoke_trust'], ['reset_preferences'], ['remove_avatar'], ['revoke_exceptions']]);

it('Auth bulk activation previews only actual changes in a mixed ten-account selection', function (): void {
    $ids = [];
    for ($index = 0; $index < 10; $index++) {
        $ids[] = app(ChangeIdentity::class)->create(new NewIdentity('mixed-bulk-'.$index.'@example.test', name: 'Mixed bulk', active: $index < 8))->publicId;
    }
    authAdministrationLogin($this, ['runa.auth.users.read', 'runa.auth.users.manage', 'runa.permissions.users.select']);
    $input = ['command' => 'activate', 'public_id' => null, 'revision' => '0', 'values' => ['public_ids' => $ids], 'confirmed' => true];
    $input['review'] = $this->postJson('/api/admin/runa.auth/users/sections/bulk_users/preview', $input)->assertOk()->json('data');
    expect($input['review']['changes']['summary'])->toMatchArray(['accounts' => 10, 'affected_accounts' => 2]);
    $this->postJson('/api/admin/runa.auth/users/sections/bulk_users/commands', $input)->assertOk();
    expect(User::whereIn('public_id', $ids)->where('active', true)->count())->toBe(10)
        ->and(User::whereIn('public_id', $ids)->where('version', 2)->count())->toBe(2);
    $input['review'] = $this->postJson('/api/admin/runa.auth/users/sections/bulk_users/preview', $input)->assertOk()->json('data');
    expect($input['review']['changes']['summary']['affected_accounts'])->toBe(0);
});

it('Auth user administration refuses changed selection and rolls back a whole failed bulk batch', function (): void {
    $first = app(ChangeIdentity::class)->create(new NewIdentity('atomic-first@example.test', name: 'Atomic first', active: true));
    $second = app(ChangeIdentity::class)->create(new NewIdentity('atomic-second@example.test', name: 'Atomic second', active: true));
    authAdministrationLogin($this, ['runa.auth.users.read', 'runa.auth.users.manage', 'runa.permissions.users.select']);
    $input = ['command' => 'deactivate', 'public_id' => null, 'revision' => '0', 'values' => ['public_ids' => [$first->publicId, $second->publicId]], 'confirmed' => true];
    $oversized = $input;
    $oversized['values']['public_ids'] = array_map(fn () => (string) Str::uuid7(), range(1, 100));
    $this->postJson('/api/admin/runa.auth/users/sections/bulk_users/preview', $oversized)->assertUnprocessable();
    $input['review'] = $this->postJson('/api/admin/runa.auth/users/sections/bulk_users/preview', $input)->assertOk()->json('data');
    DB::table('runa_auth.users')->where('public_id', $second->publicId)->increment('version');
    $this->postJson('/api/admin/runa.auth/users/sections/bulk_users/commands', $input)->assertConflict();
    expect(User::where('public_id', $first->publicId)->value('active'))->toBeTrue();
    $input['review'] = $this->postJson('/api/admin/runa.auth/users/sections/bulk_users/preview', $input)->assertOk()->json('data');
    DB::statement("ALTER TABLE runa_audit.events ADD CONSTRAINT bulk_failure CHECK (action <> 'identity.deactivate')");
    try {
        $this->postJson('/api/admin/runa.auth/users/sections/bulk_users/commands', $input)->assertStatus(503);
        expect(User::whereIn('public_id', [$first->publicId, $second->publicId])->where('active', true)->count())->toBe(2)
            ->and(DB::table('runa_auth.step_up_proofs')->where('action', 'runa.auth.users.bulk.deactivate')->exists())->toBeFalse();
    } finally {
        DB::statement('ALTER TABLE runa_audit.events DROP CONSTRAINT bulk_failure');
    }
});

it('Auth user administration reads independently granted security invitation and avatar facts without directory authority', function (): void {
    $target = app(ChangeIdentity::class)->create(new NewIdentity('least-privilege@example.test', name: 'Least privilege', active: true));
    authAdministrationLogin($this, ['runa.auth.two_factor.reset', 'runa.auth.passwords.reset', 'runa.auth.invitations.manage', 'runa.auth.avatars.manage']);
    $security = authAdministrationRead($this, 'security', $target->publicId)['values'];
    expect($security['two_factor_enabled'])->toBeFalse()->and($security['two_factor_required'])->toBeFalse()->and($security['password_set'])->toBeFalse();
    expect($security)->not->toHaveKey('email')->not->toHaveKey('active')->not->toHaveKey('created_at');
    expect(authAdministrationRead($this, 'invitations', $target->publicId)['values']['invitation_pending'])->toBeFalse();
    expect(authAdministrationRead($this, 'avatar', $target->publicId)['values']['has_avatar'])->toBeFalse();
    $this->getJson('/api/admin/runa.auth/users/sections/directory?kind=page')->assertNotFound();
});

it('Auth fresh schema and administration expose only activation and scheduled deactivation', function (): void {
    expect(DB::connection()->getSchemaBuilder()->hasColumn('runa_auth.users', 'expires_at'))->toBeFalse();
    expect(fn () => new LifecycleChange('set_expiry'))->toThrow(InvalidArgumentException::class);
    expect(AdministrationPages::commands()['bulk_users'])->not->toHaveKey('set_expiry')->not->toHaveKey('approve');
    expect(AdministrationPages::commands()['creation']['create']['inputs'])->not->toContain('approved');
    authAdministrationLogin($this, ['runa.auth.users.read', 'runa.auth.users.manage']);
    $page = $this->getJson('/api/admin/runa.auth/users')->assertOk()->json('data');
    foreach ($page['sections'] as $section) {
        if (in_array($section['key'], ['directory', 'lifecycle'], true)) {
            expect(array_column($section['fields'], 'key'))->not->toContain('expires_at', 'approved');
        }
    }
});

it('Auth bulk inversion refuses stale previews without applying a second inversion', function (): void {
    $one = app(ChangeIdentity::class)->create(new NewIdentity('invert-active@example.test', name: 'Active', active: true));
    $two = app(ChangeIdentity::class)->create(new NewIdentity('invert-inactive@example.test', name: 'Inactive'));
    authAdministrationLogin($this, ['runa.auth.users.read', 'runa.auth.users.manage', 'runa.permissions.users.select']);
    $input = ['command' => 'toggle_activation', 'public_id' => null, 'revision' => '0', 'values' => ['public_ids' => [$one->publicId, $two->publicId]], 'confirmed' => true];
    $input['review'] = $this->postJson('/api/admin/runa.auth/users/sections/bulk_users/preview', $input)->assertOk()->json('data');
    expect($input['review']['changes']['summary']['affected_accounts'])->toBe(2);
    $this->postJson('/api/admin/runa.auth/users/sections/bulk_users/commands', $input)->assertOk();
    $this->postJson('/api/admin/runa.auth/users/sections/bulk_users/commands', $input)->assertConflict();
    expect(User::where('public_id', $one->publicId)->value('active'))->toBeFalse()
        ->and(User::where('public_id', $two->publicId)->value('active'))->toBeTrue();
});

it('Auth bulk operations skip all protected administrators and apply to ordinary targets', function (string $operation): void {
    $role = DB::table('runa_permissions.roles')->where('code', 'runa.permissions.super_admin')->value('id');
    $inactiveAdmin = app(ChangeIdentity::class)->create(new NewIdentity('inactive-protected@example.test', name: 'Inactive administrator'));
    foreach ([$this->adminUser->public_id, $inactiveAdmin->publicId] as $id) {
        DB::table('runa_permissions.user_roles')->insert(['user_public_id' => $id, 'role_id' => $role, 'created_at' => now(), 'updated_at' => now()]);
    }
    $target = app(ChangeIdentity::class)->create(new NewIdentity('ordinary-bulk@example.test', name: 'Ordinary', active: true));
    authAdministrationLogin($this, ['runa.auth.users.read', 'runa.auth.users.manage', 'runa.permissions.users.select']);
    $before = DB::table('runa_auth.users')->whereIn('public_id', [$this->adminUser->public_id, $inactiveAdmin->publicId])->orderBy('public_id')->get(['public_id', 'version', 'security_version', 'preference_revision', 'active', 'password_change_required'])->toJson();
    $input = ['command' => $operation, 'public_id' => null, 'revision' => '0', 'values' => ['public_ids' => [$this->adminUser->public_id, $inactiveAdmin->publicId, $target->publicId]], 'confirmed' => true];
    $input['review'] = $this->postJson('/api/admin/runa.auth/users/sections/bulk_users/preview', $input)->assertOk()->json('data');
    expect($input['review']['changes']['summary'])->toMatchArray(['accounts' => 3, 'affected_accounts' => 1, 'skipped_accounts' => 2]);
    $this->postJson('/api/admin/runa.auth/users/sections/bulk_users/commands', $input)->assertOk();
    expect(DB::table('runa_auth.users')->whereIn('public_id', [$this->adminUser->public_id, $inactiveAdmin->publicId])->orderBy('public_id')->get(['public_id', 'version', 'security_version', 'preference_revision', 'active', 'password_change_required'])->toJson())->toBe($before);
    $user = User::where('public_id', $target->publicId)->firstOrFail();
    match ($operation) {
        'deactivate', 'toggle_activation' => expect($user->active)->toBeFalse(),
        'require_password_change' => expect($user->password_change_required)->toBeTrue(),
        'reset_preferences' => expect($user->preference_revision)->toBe(1),
    };
})->with(['deactivate', 'toggle_activation', 'require_password_change', 'reset_preferences']);

it('Auth bulk preview becomes stale when a target gains protected authority', function (): void {
    $target = app(ChangeIdentity::class)->create(new NewIdentity('promoted-bulk@example.test', name: 'Promoted', active: true));
    authAdministrationLogin($this, ['runa.auth.users.read', 'runa.auth.users.manage', 'runa.permissions.users.select']);
    $input = ['command' => 'deactivate', 'public_id' => null, 'revision' => '0', 'values' => ['public_ids' => [$target->publicId]], 'confirmed' => true];
    $input['review'] = $this->postJson('/api/admin/runa.auth/users/sections/bulk_users/preview', $input)->assertOk()->json('data');
    $role = DB::table('runa_permissions.roles')->where('code', 'runa.permissions.super_admin')->value('id');
    DB::table('runa_permissions.user_roles')->insert(['user_public_id' => $target->publicId, 'role_id' => $role, 'created_at' => now(), 'updated_at' => now()]);
    $this->postJson('/api/admin/runa.auth/users/sections/bulk_users/commands', $input)->assertConflict();
    expect(User::where('public_id', $target->publicId)->value('active'))->toBeTrue();
});

it('Auth bulk sessions preserve protected sessions while revoking ordinary selected sessions', function (): void {
    $role = DB::table('runa_permissions.roles')->where('code', 'runa.permissions.super_admin')->value('id');
    DB::table('runa_permissions.user_roles')->insert(['user_public_id' => $this->adminUser->public_id, 'role_id' => $role, 'created_at' => now(), 'updated_at' => now()]);
    $identity = app(ChangeIdentity::class)->create(new NewIdentity('ordinary-session-bulk@example.test', name: 'Ordinary', active: true));
    $target = User::where('public_id', $identity->publicId)->firstOrFail();
    DB::transaction(fn () => app(SessionStore::class)->admit(DB::connection(), $target, 'protected-bulk-fixture-session', false));
    authAdministrationLogin($this, ['runa.auth.users.read', 'runa.auth.sessions.bulk_revoke', 'runa.permissions.users.select']);
    $input = ['command' => 'revoke', 'public_id' => null, 'revision' => '0', 'values' => ['public_ids' => [$this->adminUser->public_id, $target->public_id]], 'confirmed' => true];
    $input['review'] = $this->postJson('/api/admin/runa.auth/users/sections/bulk_sessions/preview', $input)->assertOk()->json('data');
    expect($input['review']['changes']['summary'])->toMatchArray(['accounts' => 2, 'affected_accounts' => 1, 'sessions' => 1, 'skipped_accounts' => 1]);
    $this->postJson('/api/admin/runa.auth/users/sections/bulk_sessions/commands', $input)->assertOk();
    expect(DB::table('runa_auth.sessions')->where('user_id', $target->id)->whereNull('revoked_at')->count())->toBe(0)
        ->and(DB::table('runa_auth.sessions')->where('user_id', $this->adminUser->id)->whereNull('revoked_at')->count())->toBeGreaterThan(0);
    $this->getJson('/api/runa/auth/session')->assertOk();
});

it('Auth bulk delivery skips ineligible accounts without mutating their credentials or history', function (string $operation): void {
    $role = DB::table('runa_permissions.roles')->where('code', 'runa.permissions.super_admin')->value('id');
    DB::table('runa_permissions.user_roles')->insert(['user_public_id' => $this->adminUser->public_id, 'role_id' => $role, 'created_at' => now(), 'updated_at' => now()]);
    $ids = [$this->adminUser->public_id];
    foreach (['eligible', 'credential', 'inactive', 'locked', 'deleted'] as $state) {
        $identity = app(ChangeIdentity::class)->create(new NewIdentity('delivery-'.$state.'@example.test', name: $state, active: true));
        $ids[] = $identity->publicId;
        $hasPassword = $operation === 'password_reset';
        if ($state === 'credential') {
            $hasPassword = ! $hasPassword;
        }
        DB::table('runa_auth.users')->where('public_id', $identity->publicId)->update([
            'password' => $hasPassword ? Hash::make('delivery-fixture-password') : null,
            'active' => ! in_array($state, ['inactive', 'deleted'], true), 'security_locked' => $state === 'locked',
            'deleted_at' => $state === 'deleted' ? now() : null,
            'password_changed_at' => now(), 'email_verified_at' => now(),
        ]);
    }
    $access = BulkUserOperations::access($operation);
    authAdministrationLogin($this, ['runa.auth.users.read', 'runa.permissions.users.select', 'runa.auth.'.$access['permission']]);
    $input = ['command' => $operation, 'public_id' => null, 'revision' => '0', 'values' => ['public_ids' => $ids], 'confirmed' => true];
    $input['review'] = $this->postJson('/api/admin/runa.auth/users/sections/bulk_users/preview', $input)->assertOk()->json('data');
    $eligible = $operation === 'invite' ? [$ids[1], $ids[3]] : [$ids[1]];
    expect($input['review']['changes']['summary'])->toMatchArray(['accounts' => 6, 'affected_accounts' => count($eligible), 'skipped_accounts' => 6 - count($eligible), 'ineligible_accounts' => 5 - count($eligible)]);
    $before = User::withTrashed()->whereIn('public_id', array_diff($ids, $eligible))->get()->map(fn (User $user) => [$user->public_id, $user->version, $user->security_version])->all();
    if ($operation === 'password_reset') {
        $this->postJson('/api/runa/auth/step-up', ['action' => 'runa.auth.users.bulk.password_reset', 'current_password' => 'admin-entry-password'])->assertOk();
    }
    $history = DB::table('runa_audit.events')->count();
    $this->postJson('/api/admin/runa.auth/users/sections/bulk_users/commands', $input)->assertOk();
    expect(User::withTrashed()->whereIn('public_id', array_diff($ids, $eligible))->get()->map(fn (User $user) => [$user->public_id, $user->version, $user->security_version])->all())->toBe($before);
    $eligibleIds = User::whereIn('public_id', $eligible)->pluck('id')->all();
    expect(DB::table('runa_auth.entry_tokens')->where('purpose', $operation === 'invite' ? 'invitation' : 'reset')->pluck('user_id')->all())->toEqualCanonicalizing($eligibleIds)
        ->and(DB::table('runa_audit.events')->count())->toBe($history + ($operation === 'invite' ? 2 : 3));
    $input['values']['public_ids'] = array_values(array_diff($ids, $eligible));
    $empty = $this->postJson('/api/admin/runa.auth/users/sections/bulk_users/preview', $input)->assertOk()->json('data.changes.summary');
    expect($empty)->toMatchArray(['affected_accounts' => 0, 'skipped_accounts' => 6 - count($eligible), 'ineligible_accounts' => 5 - count($eligible)]);
})->with(['invite', 'password_reset']);

it('Auth bulk delivery rejects eligibility changes caused by a scheduled deactivation after review', function (): void {
    $identity = app(ChangeIdentity::class)->create(new NewIdentity('delivery-expiring@example.test', name: 'Scheduled', active: true));
    DB::table('runa_auth.users')->where('public_id', $identity->publicId)->update(['deactivate_at' => now()->addSeconds(30)]);
    authAdministrationLogin($this, ['runa.auth.users.read', 'runa.permissions.users.select', 'runa.auth.invitations.manage']);
    $input = ['command' => 'invite', 'public_id' => null, 'revision' => '0', 'values' => ['public_ids' => [$identity->publicId]], 'confirmed' => true];
    $input['review'] = $this->postJson('/api/admin/runa.auth/users/sections/bulk_users/preview', $input)->assertOk()->json('data');
    expect($input['review']['changes']['summary']['affected_accounts'])->toBe(1);
    CarbonImmutable::setTestNow(CarbonImmutable::now()->addMinute());
    try {
        $this->postJson('/api/admin/runa.auth/users/sections/bulk_users/commands', $input)->assertConflict()->assertJsonPath('error.code', 'user_preview_stale');
        expect(DB::table('runa_auth.entry_tokens')->count())->toBe(0);
    } finally {
        CarbonImmutable::setTestNow();
    }
});

it('Auth bulk previews preserve specific schedule and deleted-account refusal reasons', function (string $reason): void {
    $identity = app(ChangeIdentity::class)->create(new NewIdentity('delivery-refusal@example.test', name: 'Refusal', active: true));
    $command = 'schedule_activation';
    $values = ['public_ids' => [$identity->publicId], 'at' => now()->addDay()->utc()->format('Y-m-d\TH:i:s\Z')];
    if ($reason === 'schedule_must_be_future') {
        $values['at'] = now()->subDay()->utc()->format('Y-m-d\TH:i:s\Z');
    } elseif ($reason === 'invalid_schedule_order') {
        DB::table('runa_auth.users')->where('public_id', $identity->publicId)->update(['deactivate_at' => now()->addHour()]);
    } else {
        $command = 'activate';
        unset($values['at']);
        DB::table('runa_auth.users')->where('public_id', $identity->publicId)->update(['deleted_at' => now(), 'active' => false]);
    }
    authAdministrationLogin($this, ['runa.auth.users.read', 'runa.permissions.users.select', 'runa.auth.users.manage']);
    $this->postJson('/api/admin/runa.auth/users/sections/bulk_users/preview', ['command' => $command, 'public_id' => null, 'revision' => '0', 'values' => $values, 'confirmed' => false])->assertConflict()->assertJsonPath('error.code', $reason);
})->with(['schedule_must_be_future', 'invalid_schedule_order', 'identity_deleted']);

it('saves one user detail form without requiring unrelated aggregate participants', function (): void {
    adminAggregateLogin($this);
    $target = app(ChangeIdentity::class)->create(new NewIdentity('individual-detail@example.test', name: 'Before', active: true));
    $path = '/api/admin/runa.auth/users/sections/profile';
    $record = $this->getJson($path.'?public_id='.$target->publicId)->assertOk()->json('data');
    expect($record['values']['identifier_mode'])->toBeIn(['email', 'login', 'either']);
    $input = ['command' => 'save', 'public_id' => $target->publicId, 'revision' => $record['revision'],
        'values' => array_replace(array_intersect_key($record['values'], array_flip(['name', 'first_name', 'surname', 'login'])), ['name' => 'Individual change'])];
    $this->postJson($path.'/commands', $input)->assertOk()->assertJsonPath('data.record.values.name', 'Individual change');
    expect(DB::table('runa_audit.events')->where('action', 'assignments.changed')->count())->toBe(0);
    $this->postJson($path.'/commands', $input)->assertConflict();
});

it('edits target preferences atomically without changing the administrator or weakening delivery rules', function (): void {
    foreach (['runa.auth.preferences.read', 'runa.auth.preferences.manage', 'runa.auth.addresses.read'] as $grant) {
        ($this->adminGrant)($grant);
    }
    ($this->adminGrant)('runa.admin.enter');
    ($this->adminGrant)('runa.auth.identities.read');
    ($this->adminLogin)();
    ($this->adminProof)();
    $target = app(ChangeIdentity::class)->create(new NewIdentity('target-preferences@example.test', name: 'Target', active: false));
    $path = '/api/admin/runa.auth/users/sections/preferences';
    $record = $this->getJson($path.'?public_id='.$target->publicId)->assertOk()->json('data');
    $values = ['locale' => 'en', 'theme' => 'dark', 'delivery' => $record['values']['delivery']];
    $input = ['command' => 'save', 'public_id' => $target->publicId, 'revision' => $record['revision'], 'values' => $values];
    $this->postJson($path.'/commands', $input)->assertOk()->assertJsonPath('data.record.values.locale', 'en')->assertJsonPath('data.record.values.theme', 'dark');
    expect($this->adminUser->fresh()->preference_revision)->toBe(0)
        ->and(DB::table('runa_audit.events')->where('action', 'preferences.admin_changed')->count())->toBe(1);
    $this->postJson($path.'/commands', $input)->assertConflict();
    $fresh = $this->getJson($path.'?public_id='.$target->publicId)->assertOk()->json('data');
    $categories = json_decode($fresh['values']['categories'], true, flags: JSON_THROW_ON_ERROR);
    $mandatory = array_values(array_filter($categories, fn ($entry) => $entry['mandatory'] || $entry['sensitive']))[0];
    $bad = ['locale' => 'pl', 'theme' => 'light', 'delivery' => json_encode([['module' => $mandatory['module'], 'key' => $mandatory['key'], 'enabled' => false, 'recipients' => ['primary']]], JSON_THROW_ON_ERROR)];
    $this->postJson($path.'/commands', ['command' => 'save', 'public_id' => $target->publicId, 'revision' => $fresh['revision'], 'values' => $bad])->assertUnprocessable();
    $this->getJson($path.'?public_id='.$target->publicId)->assertOk()->assertJsonPath('data.values.locale', 'en')->assertJsonPath('data.revision', $fresh['revision']);
});

it('separates target preference read manage and recipient-address grants', function (): void {
    ($this->adminGrant)('runa.auth.preferences.read');
    ($this->adminGrant)('runa.admin.enter');
    ($this->adminGrant)('runa.auth.identities.read');
    ($this->adminLogin)();
    ($this->adminProof)();
    $target = app(ChangeIdentity::class)->create(new NewIdentity('private-preferences@example.test', name: 'Private', active: true));
    $path = '/api/admin/runa.auth/users/sections/preferences';
    $record = $this->getJson($path.'?public_id='.$target->publicId)->assertOk()->json('data');
    expect(json_decode($record['values']['recipients'], true))->toBe([])
        ->and(json_decode($record['values']['categories'], true))->toBe([]);
    $this->postJson($path.'/commands', ['command' => 'save', 'public_id' => $target->publicId, 'revision' => $record['revision'],
        'values' => ['locale' => 'en', 'theme' => 'dark', 'delivery' => '[]']])->assertUnprocessable();
    expect(User::where('public_id', $target->publicId)->value('preference_revision'))->toBe(0);
});

it('searches assignment definitions before paging and hydrates selected labels with native bounds', function (): void {
    adminAggregateLogin($this);
    $target = app(ChangeIdentity::class)->create(new NewIdentity('assignment-search@example.test', name: 'Assignment search', active: true));
    $rows = [];
    for ($index = 0; $index < 105; $index++) {
        $rows[] = ['public_id' => (string) Str::uuid7(), 'code' => 'custom.catalogue_'.$index, 'system' => false, 'name_pl' => 'Katalog '.$index, 'name_en' => 'Catalogue '.$index, 'description_pl' => '', 'description_en' => '', 'version' => 1, 'created_at' => now(), 'updated_at' => now()];
    }
    DB::table('runa_permissions.roles')->insert($rows);
    $id = (string) Str::uuid7();
    DB::table('runa_permissions.roles')->insert(['public_id' => $id, 'code' => 'custom.finance_search', 'system' => false, 'name_pl' => 'Finanse lokalne', 'name_en' => 'Local finance', 'description_pl' => '', 'description_en' => '', 'version' => 1, 'created_at' => now(), 'updated_at' => now()]);
    $path = '/api/admin/runa.auth/users/sections/runa.permissions.user_access.assignments?';
    $query = ['kind' => 'options', 'field' => 'roles', 'public_id' => $target->publicId, 'limit' => 1, 'filters' => json_encode(['search' => 'finanse', 'language' => 'pl'])];
    $this->getJson($path.http_build_query($query))->assertOk()->assertJsonPath('data.items.0.value', $id)->assertJsonPath('data.items.0.label', 'Finanse lokalne')->assertJsonPath('data.next', null);
    $query['filters'] = json_encode(['selected' => json_encode([$id]), 'language' => 'en']);
    $this->getJson($path.http_build_query($query))->assertOk()->assertJsonPath('data.items.0.label', 'Local finance');
    $query['filters'] = json_encode(['selected' => 'not-json']);
    $this->getJson($path.http_build_query($query))->assertUnprocessable();
    $query['filters'] = json_encode(['search' => str_repeat('x', 161)]);
    $this->getJson($path.http_build_query($query))->assertUnprocessable();
});

it('reads only authorized selected account avatar bytes with private headers and deleted visibility', function (): void {
    $target = app(ChangeIdentity::class)->create(new NewIdentity('avatar-reader@example.test', name: 'Target Image', active: true));
    $path = 'avatars/'.$target->publicId.'/read-fixture.png';
    $contents = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j4N0AAAAASUVORK5CYII=', true);
    app(ModuleStorage::class)->disk('runa.auth')->put($path, $contents);
    DB::table('runa_auth.users')->where('public_id', $target->publicId)->update(['avatar_path' => $path, 'avatar_type' => 'image/png']);
    authAdministrationLogin($this, []);
    $this->get('/api/runa/auth/users/avatar?public_id='.$target->publicId)->assertForbidden();
    ($this->adminGrant)('runa.auth.avatars.manage');
    $events = DB::table('runa_audit.events')->count();
    $url = '/api/runa/auth/users/avatar?public_id='.$target->publicId;
    $this->get($url)->assertOk()->assertContent($contents)->assertHeader('Content-Type', 'image/png')->assertHeader('X-Content-Type-Options', 'nosniff');
    expect($this->get($url)->headers->get('Cache-Control'))->toContain('private')->toContain('no-store');
    $this->get('/api/runa/auth/profile/avatar')->assertNotFound();
    $this->get('/api/runa/auth/users/avatar?public_id=wrong')->assertUnprocessable();
    DB::table('runa_auth.users')->where('public_id', $target->publicId)->update(['active' => false, 'deleted_at' => now()]);
    $this->get($url)->assertNotFound();
    ($this->adminGrant)('runa.auth.users.deleted.read');
    $this->get($url)->assertOk()->assertContent($contents);
    expect(DB::table('runa_audit.events')->count())->toBe($events);
});

it('preserves identifier refusals through Admin profile and secondary adapters without mutation', function (): void {
    authAdministrationLogin($this, ['runa.auth.profiles.read', 'runa.auth.profiles.manage', 'runa.auth.secondary.read', 'runa.auth.secondary.manage']);
    $target = app(ChangeIdentity::class)->create(new NewIdentity('refusal-target@example.test', name: 'Before', login: 'refusal_target'));
    app(ChangeIdentity::class)->create(new NewIdentity('occupied@example.test', name: 'Occupied', login: 'occupied_login'));
    $settings = app(ModuleSettings::class)->read('runa.auth');
    app(ModuleSettings::class)->update('runa.auth', ['secondary_emails_enabled' => true, 'secondary_email_sharing' => false], $settings['revision'], $settings['generation']);
    $profile = authAdministrationRead($this, 'profile', $target->publicId);
    $values = array_intersect_key($profile['values'], array_flip(['name', 'first_name', 'surname', 'login']));
    $values['login'] = 'occupied_login';
    $this->postJson('/api/admin/runa.auth/users/sections/profile/commands', ['command' => 'save', 'public_id' => $target->publicId, 'revision' => $profile['revision'], 'values' => $values])
        ->assertConflict()->assertJsonPath('error.code', 'identifier_conflict');
    $secondary = authAdministrationRead($this, 'secondary', $target->publicId);
    expect($secondary['values']['secondary_email_limit'])->toBe(app(ModuleSettings::class)->integer('runa.auth', 'secondary_email_limit'));
    $this->postJson('/api/admin/runa.auth/users/sections/secondary/commands', ['command' => 'add', 'public_id' => $target->publicId, 'revision' => $secondary['revision'], 'values' => ['email' => 'occupied@example.test']])
        ->assertConflict()->assertJsonPath('error.code', 'identifier_conflict');
    expect(User::where('public_id', $target->publicId)->value('login'))->toBe('refusal_target');
    expect(DB::table('runa_auth.secondary_emails')->count())->toBe(0);
});

it('preserves identifier refusals through aggregate Save and rolls back every participant', function (): void {
    adminAggregateLogin($this);
    $target = app(ChangeIdentity::class)->create(new NewIdentity('aggregate-refusal@example.test', name: 'Before', login: 'aggregate_target', active: true));
    app(ChangeIdentity::class)->create(new NewIdentity('aggregate-occupied@example.test', name: 'Occupied', login: 'aggregate_occupied'));
    $permission = DB::table('runa_permissions.permissions')->where('code', 'runa.audit.read')->value('public_id');
    $input = adminAggregateInput($this, $target->publicId, [$permission]);
    $input['participants'][1]['input']['values']['login'] = 'aggregate_occupied';
    $input = adminAggregateReview($this, $input);
    $this->postJson('/api/admin/runa.auth/users/save', $input)->assertConflict()->assertJsonPath('error.code', 'identifier_conflict');
    expect(User::where('public_id', $target->publicId)->value('name'))->toBe('Before');
    expect(User::where('public_id', $target->publicId)->value('login'))->toBe('aggregate_target');
    expect(DB::table('runa_permissions.user_permissions')->where('user_public_id', $target->publicId)->count())->toBe(0);
});

it('reports an ineligible administrative password reset without consuming proof or changing the account', function (): void {
    foreach (['runa.admin.enter', 'runa.auth.identities.read', 'runa.auth.passwords.reset'] as $grant) {
        ($this->adminGrant)($grant);
    }
    nativeMutationProof($this);
    ($this->adminProof)();
    CarbonImmutable::setTestNow(CarbonImmutable::now()->addSeconds(31));
    $this->postJson('/api/runa/auth/step-up', ['action' => 'runa.auth.password.admin_reset', 'current_password' => 'admin-entry-password'])->assertOk();
    $target = app(ChangeIdentity::class)->create(new NewIdentity('passwordless-refusal@example.test', name: 'Passwordless', active: true));
    $this->postJson('/api/admin/runa.auth/users/sections/security/commands', ['command' => 'password_reset', 'public_id' => $target->publicId, 'revision' => (string) $target->version, 'values' => [], 'confirmed' => true])->assertConflict()->assertJsonPath('error.code', 'password_reset_unavailable');
    expect(User::where('public_id', $target->publicId)->value('version'))->toBe($target->version);
    expect(DB::table('runa_auth.step_up_proofs')->where('action', 'runa.auth.password.admin_reset')->value('consumed_at'))->toBeNull();
});

it('confirms existing addresses through native Admin commands and refreshes recipient choices', function (string $kind): void {
    authAdministrationLogin($this, ['runa.auth.profiles.read', 'runa.auth.emails.read', 'runa.auth.secondary.read', 'runa.auth.secondary.manage', 'runa.auth.emails.verify_bypass', 'runa.auth.preferences.read', 'runa.auth.addresses.read']);
    DB::table('runa_auth.users')->where('id', $this->adminUser->id)->update(['email_verified_at' => now()]);
    $settings = app(ModuleSettings::class)->read('runa.auth');
    app(ModuleSettings::class)->update('runa.auth', ['email_verification' => 'all', 'secondary_emails_enabled' => true, 'admin_email_verification_bypass' => true], $settings['revision'], $settings['generation']);
    $target = app(ChangeIdentity::class)->create(new NewIdentity('verify-existing-admin@example.test', name: 'Verify existing', active: true));
    $address = $kind === 'secondary' ? app(ManageProfiles::class)->addSecondary($target->publicId, 1, 'verify-existing-secondary@example.test') : null;
    $section = $kind === 'secondary' ? 'secondary' : 'primary_email';
    $record = authAdministrationRead($this, $section, $target->publicId);
    $input = ['command' => 'verify', 'public_id' => $target->publicId, 'revision' => $record['revision'], 'values' => $address === null ? [] : ['address_id' => $address], 'confirmed' => true];
    $path = '/api/admin/runa.auth/users/sections/'.$section.'/commands';
    $this->postJson($path, $input)->assertForbidden();
    authAdministrationStrongProof($this, 'runa.auth.email.admin_bypass');
    $this->postJson($path, $input)->assertOk()->assertJsonPath('data.status', 'committed');
    if ($address === null) {
        expect(User::where('public_id', $target->publicId)->value('email_verified_at'))->not->toBeNull();
    } else {
        expect(DB::table('runa_auth.secondary_emails')->where('public_id', $address)->value('verified_at'))->not->toBeNull();
        $preferences = authAdministrationRead($this, 'preferences', $target->publicId);
        expect(array_column(json_decode($preferences['values']['recipients'], true, flags: JSON_THROW_ON_ERROR), 'value'))->toContain($address);
    }
    expect(DB::table('runa_auth.step_up_proofs')->where('action', 'runa.auth.email.admin_bypass')->whereNotNull('consumed_at')->exists())->toBeTrue()
        ->and(DB::table('runa_audit.events')->where('action', $address === null ? 'email.admin_verified' : 'secondary.admin_verified')->count())->toBe(1);
})->with(['primary', 'secondary']);

it('supplied tables order authorized server datasets before cursor paging and guard security exports', function (): void {
    $settings = app(ModuleSettings::class)->read('runa.auth');
    app(ModuleSettings::class)->update('runa.auth', ['trusted_devices_enabled' => true], $settings['revision'], $settings['generation']);
    $ids = [];
    foreach (['Zulu', 'Alpha', 'Alpha', 'Middle'] as $index => $name) {
        $identity = app(ChangeIdentity::class)->create(new NewIdentity('table-order-'.$index.'@example.test', name: 'Table order '.$name, active: true));
        $ids[] = $identity->publicId;
        DB::table('runa_auth.users')->where('public_id', $identity->publicId)->update(['login' => $index === 3 ? null : strtolower($name).$index, 'login_claim' => $index === 3 ? null : strtolower($name).$index]);
    }
    authAdministrationLogin($this, ['runa.auth.users.read', 'runa.auth.profiles.read', 'runa.auth.sessions.manage', 'runa.auth.trust.manage', 'runa.permissions.introspection.read', 'runa.audit.read']);
    foreach (['display_name', 'login', 'created_at', 'active'] as $key) {
        foreach (['asc', 'desc'] as $direction) {
            $cursor = null;
            $found = [];
            do {
                $read = authAdministrationRead($this, 'directory', query: ['kind' => 'page', 'limit' => 1, 'cursor' => $cursor, 'filters' => json_encode(['display_name' => 'Table order']), 'sort' => [['key' => $key, 'direction' => $direction]]]);
                $found = [...$found, ...array_column($read['items'], 'public_id')];
                $cursor = $read['next'];
                expect(count($found))->toBeLessThanOrEqual(4);
            } while ($cursor !== null);
            $column = $key === 'display_name' ? 'name' : $key;
            $expected = DB::table('runa_auth.users')->whereIn('public_id', $ids)->orderByRaw('"'.$column.'" '.$direction.' NULLS LAST')->orderBy('public_id')->pluck('public_id')->all();
            expect($found)->toBe($expected)->and($read['total'])->toBe(4);
        }
    }
    $base = ['kind' => 'page', 'limit' => 1, 'filters' => json_encode(['display_name' => 'Table order']), 'sort' => [['key' => 'display_name', 'direction' => 'asc']]];
    $first = authAdministrationRead($this, 'directory', query: $base);
    $this->getJson('/api/admin/runa.auth/users/sections/directory?'.http_build_query(array_replace($base, ['cursor' => $first['next'], 'sort' => [['key' => 'display_name', 'direction' => 'desc']]])))->assertUnprocessable();
    $this->getJson('/api/admin/runa.auth/users/sections/directory?'.http_build_query(['kind' => 'page', 'sort' => [['key' => 'email', 'direction' => 'asc']]]))->assertUnprocessable();
    foreach (['sessions', 'trust'] as $section) {
        $path = '/api/admin/runa.auth/users/sections/'.$section.'?'.http_build_query(['kind' => 'record', 'public_id' => $ids[0], 'export' => 1]);
        $this->getJson($path)->assertForbidden();
    }
    ($this->adminGrant)('runa.permissions.tables.export');
    foreach (['sessions', 'trust'] as $section) {
        $this->getJson('/api/admin/runa.auth/users/sections/'.$section.'?'.http_build_query(['kind' => 'record', 'public_id' => $ids[0], 'export' => 1]))->assertOk();
    }
    foreach (['asc', 'desc'] as $direction) {
        $path = '/api/admin/runa.permissions/permissions/sections/catalogue';
        $base = ['kind' => 'page', 'limit' => 1, 'filters' => json_encode(['code' => 'runa.auth']), 'sort' => [['key' => 'code', 'direction' => $direction]]];
        $first = $this->getJson($path.'?'.http_build_query($base))->assertOk()->json('data');
        $second = $this->getJson($path.'?'.http_build_query($base + ['cursor' => $first['next']]))->assertOk()->json('data');
        $expected = DB::table('runa_permissions.permissions')->whereNull('deleted_at')->where('code', 'like', '%runa.auth%')->orderBy('code', $direction)->limit(2)->pluck('public_id')->all();
        expect([$first['items'][0]['public_id'], $second['items'][0]['public_id']])->toBe($expected);
    }
    $scope = app(PrincipalScope::class);
    $scope->run(new TrustedPrincipal($this->adminUser->public_id), function () use ($ids): void {
        foreach (['zulu', 'alpha', 'alpha', 'middle'] as $name) {
            DB::transaction(fn () => app(ModuleApis::class)->resolve('runa.audit', AuditRecords::class, '1.0.0')->record(new AuditEvent('runa.auth', 'table.'.$name, entities: [new AuditEntity('runa.auth', 'table_fixture', $ids[0])])));
        }
    });
    foreach (['action', 'actor_label', 'occurred_at', 'correlation_id'] as $key) {
        foreach (['asc', 'desc'] as $direction) {
            $cursor = null;
            $found = [];
            do {
                $read = $this->getJson('/api/admin/runa.audit/history/sections/catalogue?'.http_build_query(['kind' => 'page', 'limit' => 1, 'cursor' => $cursor, 'filters' => json_encode(['module' => 'runa.auth', 'entity_module' => 'runa.auth', 'entity_type' => 'table_fixture', 'entity_public_id' => $ids[0]]), 'sort' => [['key' => $key, 'direction' => $direction]]]))->assertOk()->json('data');
                $found = [...$found, ...array_column($read['items'], 'public_id')];
                $cursor = $read['next'];
                expect(count($found))->toBeLessThanOrEqual(4);
            } while ($cursor !== null);
            $expected = DB::table('runa_audit.events')->where('module', 'runa.auth')->where('action', 'like', 'table.%')->orderByRaw('"'.$key.'" '.$direction.' NULLS LAST')->orderBy('id')->pluck('public_id')->all();
            expect($found)->toBe($expected);
        }
    }
});

it('named settings snapshots load without publishing and require transactional password proof for deletion', function (): void {
    authAdministrationLogin($this, ['runa.auth.policy.read', 'runa.auth.policy.write']);
    $url = '/api/admin/runa.auth/policy/sections/snapshots';
    $state = $this->getJson($url)->assertOk()->json('data');
    $before = app(ModuleSettings::class)->read('runa.auth');
    $capture = ['command' => 'capture', 'public_id' => null, 'revision' => $state['revision'], 'values' => ['name' => 'Before security review']];
    $created = $this->postJson($url.'/commands', $capture)->assertOk()->json('data.record');
    $catalog = json_decode($created['values']['snapshots'], true, flags: JSON_THROW_ON_ERROR);
    expect($catalog)->toHaveCount(1)->and($catalog[0]['name'])->toBe('Before security review');
    $id = $catalog[0]['public_id'];
    expect(DB::table('shared.module_settings_snapshots')->where('public_id', $id)->value('settings_values'))->not->toContain('public_welcome_enabled');
    $loaded = $this->getJson($url.'?public_id='.$id)->assertOk()->json('data');
    $values = json_decode($loaded['values']['values'], true, flags: JSON_THROW_ON_ERROR);
    expect($values['public_welcome_enabled'])->toBe($before['settings']['public_welcome_enabled']['value'])
        ->and(app(ModuleSettings::class)->read('runa.auth')['revision'])->toBe($before['revision']);
    $this->postJson($url.'/commands', $capture)->assertConflict();
    $delete = ['command' => 'delete', 'public_id' => null, 'revision' => $state['revision'], 'values' => ['snapshot_id' => $id], 'confirmed' => true];
    $this->postJson($url.'/commands', $delete)->assertForbidden()->assertJsonPath('error.code', 'step_up_required');
    expect(DB::table('shared.module_settings_snapshots')->where('public_id', $id)->value('deleted_at'))->toBeNull();
    authAdministrationStrongProof($this, 'runa.auth.policy.snapshot_delete');
    DB::statement("ALTER TABLE runa_audit.events ADD CONSTRAINT snapshot_audit_refusal CHECK (action <> 'settings.snapshot_deleted') NOT VALID");
    try {
        expect($this->postJson($url.'/commands', $delete)->status())->toBeGreaterThanOrEqual(500);
        expect(DB::table('shared.module_settings_snapshots')->where('public_id', $id)->value('deleted_at'))->toBeNull()
            ->and(DB::table('runa_auth.step_up_proofs')->where('action', 'runa.auth.policy.snapshot_delete')->value('consumed_at'))->toBeNull();
    } finally {
        DB::statement('ALTER TABLE runa_audit.events DROP CONSTRAINT snapshot_audit_refusal');
    }
    $this->postJson($url.'/commands', $delete)->assertOk();
    $this->getJson($url.'?public_id='.$id)->assertNotFound();
    expect(DB::table('runa_audit.events')->whereIn('action', ['settings.snapshot_created', 'settings.snapshot_deleted'])->count())->toBe(2)
        ->and(DB::table('runa_auth.step_up_proofs')->where('action', 'runa.auth.policy.snapshot_delete')->whereNotNull('consumed_at')->exists())->toBeTrue();
});

it('Permissions navigation stays definition-only and selected-user source lookups respect native read authority', function (): void {
    authAdministrationLogin($this, ['runa.auth.users.read', 'runa.permissions.introspection.read', 'runa.permissions.access.manage', 'runa.auth.eligibility.read']);
    $source = app(ChangeIdentity::class)->create(new NewIdentity('source-chooser@example.test', name: 'Source chooser', active: true));
    $owners = $this->getJson('/api/admin')->assertOk()->json('data.owners');
    $permissions = collect($owners)->firstWhere('id', 'runa.permissions');
    expect(array_column($permissions['pages'], 'key'))->toBe(['permissions', 'roles', 'presets'])
        ->and($permissions['default_page'])->toBe('permissions');
    $this->getJson('/api/admin/runa.permissions/access')->assertNotFound();
    $this->postJson('/api/admin/runa.permissions/permissions/sections/editor/preview', permissionDefinitionInput('role', 'refused_permission'))->assertUnprocessable();
    $path = '/api/admin/runa.auth/users/sections/runa.permissions.user_access.assignments';
    $query = ['kind' => 'options', 'field' => 'source_user', 'public_id' => $this->adminUser->public_id, 'filters' => json_encode(['search' => 'Source chooser', 'language' => 'pl'])];
    $this->getJson($path.'?'.http_build_query($query))->assertOk()->assertJsonPath('data.items.0.value', $source->publicId)->assertJsonPath('data.items.0.label', 'Source chooser');
    $permission = DB::table('runa_permissions.permissions')->where('code', 'runa.audit.read')->first();
    $this->getJson('/api/admin/runa.permissions/roles/sections/editor?'.http_build_query(['kind' => 'options', 'field' => 'permissions', 'filters' => json_encode(['search' => 'runa.audit.read', 'language' => 'pl'])]))->assertOk()->assertJsonPath('data.items.0.value', $permission->public_id)->assertJsonPath('data.items.0.label', $permission->name_pl);
    DB::table('runa_permissions.user_permissions')->where('user_public_id', $this->adminUser->public_id)->where('permission_id', DB::table('runa_permissions.permissions')->where('code', 'runa.auth.users.read')->value('id'))->delete();
    $this->getJson($path.'?'.http_build_query($query))->assertNotFound()->assertJsonMissingPath('data');
});
