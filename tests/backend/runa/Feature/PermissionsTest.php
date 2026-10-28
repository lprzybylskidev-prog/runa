<?php

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Connection;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\AuthorityGuard;
use Runa\Contracts\Modules\DictionaryEntry;
use Runa\Contracts\Modules\OperationAuthorizer;
use Runa\Contracts\Modules\OperationContext;
use Runa\Contracts\Modules\RoleDefinition;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\Integration\Core\DenyAuthorityChanges;
use Runa\Integration\Core\DenyProtectedOperations;
use Runa\Integration\Core\ProtectedAuthority;
use Runa\ModuleFramework\Api\ModuleDeclarations;
use Runa\ModuleFramework\Api\ModuleHttp;
use Runa\ModuleFramework\Api\OperationGuard;
use Runa\ModuleFramework\Api\PrincipalScope;
use Runa\ModuleFramework\Data\AuthorityLock;
use Runa\ModuleFramework\Management\ModuleManagement;
use Runa\ModuleFramework\ModuleAutoloader;
use Runa\ModuleFramework\ModuleDiscovery;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\ModuleFramework\Updates\ModuleUpdates;
use Runa\Modules\Audit\Contracts\AuditUnavailable;
use Runa\Modules\Audit\Storage\AppendEvents;
use Runa\Modules\Auth\Actions\ChangeIdentity;
use Runa\Modules\Auth\Actions\EntryTokens;
use Runa\Modules\Auth\Actions\OperatorAccounts;
use Runa\Modules\Auth\Actions\Passwords;
use Runa\Modules\Auth\Actions\ScheduledLifecycle;
use Runa\Modules\Auth\Contracts\AuthBulkSessions;
use Runa\Modules\Auth\Contracts\AuthIdentities;
use Runa\Modules\Auth\Contracts\DevelopmentAccounts;
use Runa\Modules\Auth\Contracts\IdentityConflict;
use Runa\Modules\Auth\Contracts\LifecycleChange;
use Runa\Modules\Auth\Contracts\NewIdentity;
use Runa\Modules\Auth\Contracts\SessionSelection;
use Runa\Modules\Auth\Development\Accounts;
use Runa\Modules\Auth\Development\AuthDevelopmentSeeder;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Permissions\Actions\SynchronizeDeclarations;
use Runa\Modules\Permissions\Contracts\AssignmentSet;
use Runa\Modules\Permissions\Contracts\DefinitionInput;
use Runa\Modules\Permissions\Contracts\Permissions;
use Runa\Modules\Permissions\Contracts\PermissionsAccess;
use Runa\Modules\Permissions\Contracts\PermissionsAuthority;
use Runa\Modules\Permissions\Development\Access;
use Runa\Modules\Permissions\Support\AccessState;
use Runa\Modules\Permissions\Support\DeclarationCatalog;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Development\ModuleContractSuite;
use Runa\Support\Modules\Settings\ModuleSettings;
use Runa\Support\Seeding\DevelopmentContributor;
use Runa\Support\Seeding\DevelopmentFixtures;
use Runa\Support\Seeding\SeedGraph;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
use Tests\Fixtures\CoreModuleStorage;
use Tests\Fixtures\ModuleSources;

beforeEach(function (): void {
    foreach (array_keys(CoreModuleStorage::TABLES) as $code) {
        if (DB::selectOne('SELECT 1 FROM pg_namespace WHERE nspname = ?', ['runa_'.$code])) {
            throw new RuntimeException('Permissions fixture requires absent disposable schemas.');
        }
    }
    $this->permissionsStorageOwned = true;
    app(ModuleUpdates::class)->apply('runa', str_repeat('d', 40), static function (): void {});
    app()->bind(AuthorityGuard::class, fn () => new class implements AuthorityGuard
    {
        public function assertMayRemove(Connection $connection, array $publicIds): void {}
    });
    $this->permissionUser = app(ChangeIdentity::class)->create(new NewIdentity('permission-fixture@example.test', name: 'Permission fixture', active: true, approved: true));
    // Fixture policy disables verification; credentials and step-up are exercised by Auth suites.
    $settings = app(ModuleSettings::class)->read('runa.auth');
    app(ModuleSettings::class)->update('runa.auth', ['email_verification' => 'disabled'], $settings['revision'], $settings['generation']);
    $this->permissionApi = app(ModuleApis::class)->resolve('runa.permissions', Permissions::class, '1.0.0');
    $this->asPermissionUser = fn (Closure $work) => app(PrincipalScope::class)->run(new TrustedPrincipal($this->permissionUser->publicId), $work);
    $this->directPermission = function (string $code): int {
        $id = DB::table('runa_permissions.permissions')->where('code', $code)->value('id');
        DB::table('runa_permissions.user_permissions')->insertOrIgnore(['user_public_id' => $this->permissionUser->publicId, 'permission_id' => $id, 'created_at' => now(), 'updated_at' => now()]);

        return $id;
    };
});

afterEach(function (): void {
    if (! ($this->permissionsStorageOwned ?? false)) {
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
    DB::purge('permissions_competitor');
    if (isset($this->permissionSources)) {
        $id = DB::table('shared.modules')->where('module_id', 'app.permission_fixture')->value('id');
        DB::table('shared.modules')->where('id', $id)->delete();
        $this->permissionSources->remove();
        app(ModuleRegistry::class)->synchronize('app');
    }
});

it('initializes current Permissions dictionaries without grants and authorizes direct grants without recursion', function (): void {
    expect(DB::table('runa_permissions.roles')->where('system', true)->count())->toBe(1)
        ->and(DB::table('runa_permissions.user_roles')->count())->toBe(0)
        ->and(DB::table('runa_permissions.user_permissions')->count())->toBe(0);
    ($this->asPermissionUser)(function (): void {
        expect($this->permissionApi->allows('runa.audit.read'))->toBeFalse();
        ($this->directPermission)('runa.audit.read');
        expect($this->permissionApi->allows('runa.audit.read'))->toBeTrue();
        $context = app(OperationGuard::class)->enter('runa.audit', new ApiOperation('history.test', 'runa.audit.read'));
        expect($context->moduleId)->toBe('runa.audit');
        $gate = app(Gate::class)->forUser(User::where('public_id', $this->permissionUser->publicId)->first());
        expect($gate->allows('runa.audit.read'))->toBeTrue();
        $gate->define('runa.audit.read', static fn () => false);
        expect($gate->allows('runa.audit.read', ['record']))->toBeFalse();
    });
    $events = DB::table('runa_audit.events')->count();
    app(ModuleUpdates::class)->apply('runa', str_repeat('d', 40), static function (): void {});
    expect(DB::table('runa_audit.events')->count())->toBe($events);
});

it('unions direct and role grants and reads cross-worker changes without stale authorization caches', function (): void {
    $permission = ($this->directPermission)('runa.audit.read');
    $role = DB::table('runa_permissions.roles')->insertGetId(['public_id' => (string) Str::uuid7(), 'code' => 'custom_reader', 'name_pl' => 'Czytelnik', 'name_en' => 'Reader', 'description_pl' => 'Odczyt.', 'description_en' => 'Read.', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('runa_permissions.role_permissions')->insert(['role_id' => $role, 'permission_id' => $permission, 'created_at' => now(), 'updated_at' => now()]);
    DB::table('runa_permissions.user_roles')->insert(['user_public_id' => $this->permissionUser->publicId, 'role_id' => $role, 'created_at' => now(), 'updated_at' => now()]);
    ($this->directPermission)('runa.permissions.introspection.read');
    ($this->asPermissionUser)(function () use ($permission, $role): void {
        $page = $this->permissionApi->explain($this->permissionUser->publicId);
        $read = collect($page->permissions)->firstWhere('code', 'runa.audit.read');
        expect($read['direct'])->toBeTrue()->and($read['roles'])->toHaveCount(1)->and($read['effective'])->toBeTrue();
        config()->set('database.connections.permissions_competitor', config('database.connections.pgsql'));
        $other = DB::connection('permissions_competitor');
        $other->table('runa_permissions.user_permissions')->where('permission_id', $permission)->delete();
        expect($this->permissionApi->allows('runa.audit.read'))->toBeTrue();
        $other->table('runa_permissions.user_roles')->where('role_id', $role)->delete();
        expect($this->permissionApi->allows('runa.audit.read'))->toBeFalse();
    });
});

it('does not turn superadmin into an eligibility availability unknown-permission or policy bypass', function (): void {
    $role = DB::table('runa_permissions.roles')->where('system', true)->value('id');
    DB::table('runa_permissions.user_roles')->insert(['user_public_id' => $this->permissionUser->publicId, 'role_id' => $role, 'created_at' => now(), 'updated_at' => now()]);
    ($this->asPermissionUser)(function (): void {
        expect($this->permissionApi->allows('runa.audit.read'))->toBeTrue()
            ->and($this->permissionApi->allows('runa.audit.unknown'))->toBeFalse();
        DB::table('runa_auth.users')->where('public_id', $this->permissionUser->publicId)->update(['security_locked' => true]);
        expect($this->permissionApi->allows('runa.audit.read'))->toBeFalse();
        DB::table('runa_auth.users')->where('public_id', $this->permissionUser->publicId)->update(['security_locked' => false]);
        DB::table('shared.modules')->where('module_id', 'runa.audit')->update(['source_present' => false]);
        DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
        $context = new OperationContext('runa.audit', 'permission.check', (string) Str::uuid7(), (string) Str::uuid7(), app(ModuleRegistry::class)->snapshot()->generation);
        expect(app(OperationAuthorizer::class)->allows($context, 'runa.audit.read'))->toBeFalse();
    });
});

it('protects introspection and default denial before readiness', function (): void {
    expect(fn () => $this->permissionApi->explain($this->permissionUser->publicId))->toThrow(OperationRejected::class);
    ($this->asPermissionUser)(function (): void {
        expect(fn () => $this->permissionApi->explain($this->permissionUser->publicId))->toThrow(OperationRejected::class);
        ($this->directPermission)('runa.audit.read');
        DB::table('shared.modules')->where('module_id', 'runa.permissions')->update(['installed_version' => null]);
        DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
        $context = new OperationContext('runa.audit', 'permission.check', (string) Str::uuid7(), (string) Str::uuid7(), app(ModuleRegistry::class)->snapshot()->generation);
        expect(app(OperationAuthorizer::class)->allows($context, 'runa.audit.read'))->toBeFalse();
    });
});

it('binds declaration previews and rolls back dictionary writes when required Audit fails', function (): void {
    $sync = app(SynchronizeDeclarations::class);
    $preview = $sync->preview('runa.audit');
    expect($preview->changes)->toBe([]);
    DB::table('runa_permissions.permissions')->where('code', 'runa.audit.read')->update(['name_en' => 'Old label']);
    DB::transaction(function () use ($sync, $preview): void {
        expect(fn () => $sync->apply(DB::connection(), $preview, app(AppendEvents::class)))->toThrow(RuntimeException::class, 'stale');
        $current = $sync->preview('runa.audit');
        DB::statement('ALTER TABLE runa_audit.events ADD CONSTRAINT permissions_fixture_refusal CHECK (false) NOT VALID');
        expect(fn () => $sync->apply(DB::connection(), $current, app(AppendEvents::class)))->toThrow(AuditUnavailable::class);
        expect(DB::table('runa_permissions.permissions')->where('code', 'runa.audit.read')->value('name_en'))->toBe('Old label');
    });
});

it('rejects foreign role members duplicate declarations and protected system-role source replacement', function (): void {
    $dictionary = new DictionaryEntry('reader', 'Czytelnik', 'Reader', 'Odczyt.', 'Read.');
    expect(fn () => new RoleDefinition($dictionary, ['read', 'read']))->toThrow(InvalidArgumentException::class)
        ->and(fn () => new RoleDefinition(new DictionaryEntry('runa.permissions.super_admin', 'System', 'System', 'System.', 'System.'), []))->toThrow(InvalidArgumentException::class);
    DB::table('runa_permissions.permissions')->where('code', 'runa.audit.read')->update(['module_id' => null]);
    expect(fn () => app(SynchronizeDeclarations::class)->preview('runa.audit'))->toThrow(RuntimeException::class, 'owned elsewhere');
});

it('previews affected users audits predefined membership changes and retains removed source definitions', function (): void {
    $this->permissionSources = new ModuleSources;
    $source = $this->permissionSources->add('app', 'permission_fixture');
    $source['declarations']->management = ['App\\Modules\\PermissionFixture\\Definitions\\Permissions'];
    $this->permissionSources->write('app', 'permission_fixture', $source);
    file_put_contents($this->permissionSources->root.'/app/modules/permission_fixture/backend/Definitions/Permissions.php', <<<'PHP'
        <?php
        namespace App\Modules\PermissionFixture\Definitions;
        use Runa\Contracts\Modules\DictionaryEntry;
        use Runa\Contracts\Modules\ManagementPermission;
        use Runa\Contracts\Modules\RoleDefinition;
        use Runa\Contracts\Modules\RoleProvider;
        final class Permissions implements RoleProvider {
            public static string $mode = 'initial';
            public function permissions(): array {
                if (self::$mode === 'removed') return [];
                $permission = new ManagementPermission(self::$mode === 'foreign' ? 'runa.auth.users.manage' : 'read', ['pl' => 'Odczyt', 'en' => 'Read'], ['pl' => 'Odczyt.', 'en' => 'Read.']);
                return self::$mode === 'duplicate' ? [$permission, $permission] : [$permission];
            }
            public function roles(): array {
                return self::$mode === 'removed' ? [] : [new RoleDefinition(new DictionaryEntry('reader', 'Czytelnik', 'Reader', 'Odczyt.', 'Read.'), self::$mode === 'changed' ? [] : ['read'])];
            }
            public function actions(): array { return []; }
            public function components(): array { return []; }
        }
        PHP);
    $registry = new ModuleRegistry(DB::connection(), new ModuleDiscovery($this->permissionSources->root, true));
    $registry->synchronize('app');
    $loader = new ModuleAutoloader($this->permissionSources->root, $registry, true);
    $declarations = new ModuleDeclarations($this->permissionSources->root, app(), $loader, true);
    $catalog = new DeclarationCatalog($registry, $declarations);
    $sync = new SynchronizeDeclarations(app('db'), $catalog, $registry, app(AuthorityLock::class));
    $preview = $sync->preview('app.permission_fixture');
    DB::transaction(fn () => $sync->apply(DB::connection(), $preview, app(AppendEvents::class)));
    expect($sync->preview('app.permission_fixture')->changes)->toBe([]);
    $role = DB::table('runa_permissions.roles')->where('code', 'app.permission_fixture.reader')->value('id');
    DB::table('runa_permissions.user_roles')->insert(['user_public_id' => $this->permissionUser->publicId, 'role_id' => $role, 'created_at' => now(), 'updated_at' => now()]);
    App\Modules\PermissionFixture\Definitions\Permissions::$mode = 'changed';
    $preview = $sync->preview('app.permission_fixture');
    expect($preview->affectedUsers)->toBe(1)->and($preview->changes['roles']['app.permission_fixture.reader']['members_before'])->toBe(['app.permission_fixture.read']);
    DB::transaction(fn () => $sync->apply(DB::connection(), $preview, app(AppendEvents::class)));
    expect(DB::table('runa_permissions.role_permissions')->where('role_id', $role)->count())->toBe(0)
        ->and(DB::table('runa_permissions.user_roles')->where('role_id', $role)->count())->toBe(1);
    App\Modules\PermissionFixture\Definitions\Permissions::$mode = 'removed';
    expect($sync->preview('app.permission_fixture')->changes)->toBe([])
        ->and(DB::table('runa_permissions.permissions')->where('code', 'app.permission_fixture.read')->exists())->toBeTrue();
    foreach (['foreign', 'duplicate'] as $mode) {
        App\Modules\PermissionFixture\Definitions\Permissions::$mode = $mode;
        expect(fn () => $catalog->read('app.permission_fixture'))->toThrow(RuntimeException::class);
    }
    expect(DB::table('runa_audit.events')->where('action', 'declaration.synchronized')->whereRaw("context::jsonb ->> 'code' = ?", ['app.permission_fixture.reader'])->count())->toBe(2);
});

it('preserves Optional module grants while disabled and restores them when reenabled', function (): void {
    app(ModuleRegistry::class)->synchronize('app');
    $reference = app(ModuleRegistry::class)->snapshot()->modules['app.reference'];
    DB::table('shared.modules')->where('module_id', 'app.reference')->update(['installed_version' => $reference->sourceVersion, 'enabled' => false]);
    DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    DB::table('runa_permissions.permissions')->insert(['public_id' => (string) Str::uuid7(), 'module_id' => 'app.reference', 'code' => 'app.reference.read', 'name_pl' => 'Odczyt', 'name_en' => 'Read', 'description_pl' => 'Odczyt.', 'description_en' => 'Read.', 'created_at' => now(), 'updated_at' => now()]);
    ($this->directPermission)('app.reference.read');
    ($this->asPermissionUser)(function (): void {
        expect($this->permissionApi->allows('app.reference.read'))->toBeFalse();
        DB::table('shared.modules')->where('module_id', 'app.reference')->update(['enabled' => true]);
        DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
        expect($this->permissionApi->allows('app.reference.read'))->toBeTrue();
    });
    DB::table('shared.modules')->where('module_id', 'app.reference')->update(['enabled' => false, 'installed_version' => null]);
    DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
});

/** Real password, factor enrollment and session-bound proof, without a mocked authorizer. */
function permissionAccessManager($test): array
{
    config()->set('hashing.bcrypt.rounds', 4);
    $user = User::where('public_id', $test->permissionUser->publicId)->firstOrFail();
    DB::transaction(function () use ($user): void {
        $user->password = Hash::make('permission-access-password');
        $user->email_verified_at = now();
        $user->save();
    });
    foreach (['runa.permissions.access.manage', 'runa.permissions.introspection.read', 'runa.auth.eligibility.read'] as $code) {
        ($test->directPermission)($code);
    }
    // Module installation happens after application boot; register current declarations on an isolated router.
    $routes = new Router(app('events'), app());
    (new ModuleHttp(app(ModuleRegistry::class), app(ModuleDeclarations::class), $routes, DB::connection()))->register();
    foreach ($routes->getRoutes() as $route) {
        app('router')->getRoutes()->add($route);
    }
    app('router')->getRoutes()->refreshNameLookups();
    $test->postJson('/api/runa/auth/login', ['identifier' => $user->email, 'password' => 'permission-access-password'])->assertOk();
    $test->withCredentials()->withCookie('runa_session', app('session')->getId());
    $begin = $test->postJson('/api/runa/auth/security/totp-begin', ['current_password' => 'permission-access-password'])->assertOk()->json('data');
    $code = app(Google2FA::class)->oathTotp($begin['secret'], intdiv(now()->getTimestamp(), 30));
    $codes = $test->postJson('/api/runa/auth/security/totp-confirm', ['current_password' => 'permission-access-password', 'enrollment_code' => $code])->assertOk()->json('data.recovery_codes');
    $test->permissionAccess = app(ModuleApis::class)->resolve('runa.permissions', PermissionsAccess::class, '1.0.0');

    return $codes;
}

function permissionAccessProof($test, string $code, string $action = 'runa.permissions.access.manage'): void
{
    $test->postJson('/api/runa/auth/step-up', ['action' => $action, 'requirement' => 'both',
        'current_password' => 'permission-access-password', 'method' => 'recovery', 'code' => $code])->assertOk();
    $request = Request::create('/', 'POST');
    $request->setLaravelSession(app('session')->driver());
    app()->instance('request', $request);
}

function permissionDefinition(string $kind, string $code, array $permissions = [], array $roles = [], ?string $id = null, ?int $version = null, ?string $source = null): DefinitionInput
{
    return new DefinitionInput($kind,
        new DictionaryEntry('custom.'.$code, 'Dostęp', 'Access', 'Własny dostęp.', 'Custom access.'),
        new AssignmentSet($roles, $permissions), $id, $version, $source);
}

it('applies reviewed ordinary access snapshots with real step up and preserves direct versus role semantics', function (): void {
    $codes = permissionAccessManager($this);
    $target = app(ChangeIdentity::class)->create(new NewIdentity('access-target@example.test', name: 'Target', active: true, approved: true));
    $read = DB::table('runa_permissions.permissions')->where('code', 'runa.audit.read')->value('public_id');
    ($this->asPermissionUser)(function () use ($codes, $target, $read): void {
        $api = $this->permissionAccess;
        $management = app(ModuleManagement::class)->read('runa.permissions');
        expect($management->diagnostics)->not->toContain('management_declaration_unavailable')
            ->and(array_keys($management->entities))->toBe(['assignment', 'authority', 'preset', 'role']);
        expect($api->definition('permission', $read)['dictionary']['code'])->toBe('runa.audit.read')
            ->and(collect($api->definitions('permission')['items'])->pluck('public_id')->all())->toContain($read);
        expect($this->permissionApi->allows('runa.audit.read'))->toBeFalse();
        $draft = $api->previewDefinition(permissionDefinition('role', 'reader', [$read]));
        // Managing ordinary rights does not require the actor to hold those rights.
        expect(fn () => $api->saveDefinition($draft))->toThrow(OperationRejected::class);
        permissionAccessProof($this, $codes[0]);
        $role = $api->saveDefinition($draft);
        $preview = $api->previewAssignments($target->publicId, new AssignmentSet([$role['public_id']]));
        expect($preview->effectiveAdded)->toBe(['runa.audit.read']);
        permissionAccessProof($this, $codes[1]);
        $result = $api->applyAssignments($preview);
        expect($result['revision'])->toBe(1)->and($result['assigned']['permissions'])->toBe([])->and($result['assigned']['roles'])->toBe([$role['public_id']]);
        expect(fn () => $api->applyAssignments($preview))->toThrow(OperationRejected::class);
        permissionAccessProof($this, $codes[2]);
        $same = $api->previewAssignments($target->publicId, new AssignmentSet([$role['public_id'], $role['public_id']]));
        expect($api->applyAssignments($same)['revision'])->toBe(1);
        $copy = $api->previewCopy($target->publicId, $this->permissionUser->publicId);
        expect($copy->requested->roles)->toBe([$role['public_id']])->and($copy->requested->permissions)->toBe([]);
        $presetDraft = $api->previewDefinition(permissionDefinition('preset', 'saved_reader', source: $target->publicId));
        permissionAccessProof($this, $codes[3]);
        $preset = $api->saveDefinition($presetDraft);
        expect($preset['assigned'])->toBe($result['assigned']);
        $apply = $api->previewPreset($preset['public_id'], $this->permissionUser->publicId);
        permissionAccessProof($this, $codes[4]);
        $api->applyAssignments($apply);
        expect($this->permissionApi->allows('runa.audit.read'))->toBeTrue();
        $edit = $api->previewDefinition(permissionDefinition('preset', 'saved_reader', id: $preset['public_id'], version: 1));
        permissionAccessProof($this, $codes[5]);
        $api->saveDefinition($edit);
        expect($api->assignments($target->publicId)['assigned'])->toBe($result['assigned'])
            ->and($api->assignments($this->permissionUser->publicId)['assigned']['roles'])->toBe([$role['public_id']]);
        permissionAccessProof($this, $codes[6]);
        $api->deleteDefinition('preset', $preset['public_id'], 2);
        expect($this->permissionApi->allows('runa.audit.read'))->toBeTrue();
        expect(DB::table('runa_audit.events')->where('action', 'assignments.changed')->count())->toBe(2);
    });
})->group('permissions-access');

it('refuses stale access previews and protected roles and rolls back aggregate changes with required Audit', function (): void {
    $codes = permissionAccessManager($this);
    $target = app(ChangeIdentity::class)->create(new NewIdentity('access-rollback@example.test', name: 'Target', active: true, approved: true));
    $read = DB::table('runa_permissions.permissions')->where('code', 'runa.audit.read')->value('public_id');
    ($this->asPermissionUser)(function () use ($codes, $target, $read): void {
        $api = $this->permissionAccess;
        $system = DB::table('runa_permissions.roles')->where('system', true)->value('public_id');
        expect(fn () => $api->previewAssignments($target->publicId, new AssignmentSet([$system])))->toThrow(OperationRejected::class);
        $preview = $api->previewAssignments($target->publicId, new AssignmentSet(permissions: [$read]));
        permissionAccessProof($this, $codes[0]);
        DB::statement("ALTER TABLE runa_audit.events ADD CONSTRAINT access_fixture_refusal CHECK (action <> 'assignments.changed') NOT VALID");
        DB::transaction(function () use ($api, $preview, $target): void {
            expect(fn () => $api->applyAssignments($preview))->toThrow(AuditUnavailable::class);
            expect(DB::table('runa_permissions.user_permissions')->where('user_public_id', $target->publicId)->count())->toBe(0)
                ->and(DB::table('runa_permissions.user_access')->where('user_public_id', $target->publicId)->count())->toBe(0)
                ->and(DB::table('runa_auth.step_up_proofs')->where('action', 'runa.permissions.access.manage')->value('consumed_at'))->toBeNull();
        });
        DB::statement('ALTER TABLE runa_audit.events DROP CONSTRAINT access_fixture_refusal');
        $api->applyAssignments($preview);
        permissionAccessProof($this, $codes[1]);
        expect(fn () => $api->applyAssignments($preview))->toThrow(OperationRejected::class);
        $replace = $api->previewAssignments($target->publicId, new AssignmentSet, 'replace');
        expect($replace->effectiveRemoved)->toBe(['runa.audit.read']);
        $api->applyAssignments($replace);
        expect($api->assignments($target->publicId)['assigned']['permissions'])->toBe([]);
        // ABA is rejected by the monotonic assignment revision even when content returns to empty.
        permissionAccessProof($this, $codes[2]);
        expect(fn () => $api->applyAssignments($preview))->toThrow(OperationRejected::class);
        $missing = (string) Str::uuid7();
        expect(fn () => $api->previewAssignments($missing, new AssignmentSet))->toThrow(OperationRejected::class);
    });
})->group('permissions-access');

it('binds custom role membership and preset previews to versions and retains RESTRICT references', function (): void {
    $codes = permissionAccessManager($this);
    $read = DB::table('runa_permissions.permissions')->where('code', 'runa.audit.read')->value('public_id');
    ($this->asPermissionUser)(function () use ($codes, $read): void {
        $api = $this->permissionAccess;
        $draft = $api->previewDefinition(permissionDefinition('role', 'versioned', [$read]));
        permissionAccessProof($this, $codes[0]);
        $role = $api->saveDefinition($draft);
        $presetDraft = $api->previewDefinition(permissionDefinition('preset', 'template', roles: [$role['public_id']]));
        permissionAccessProof($this, $codes[1]);
        $preset = $api->saveDefinition($presetDraft);
        $staleApply = $api->previewPreset($preset['public_id'], $this->permissionUser->publicId);
        $edit = $api->previewDefinition(permissionDefinition('role', 'versioned', id: $role['public_id'], version: 1));
        permissionAccessProof($this, $codes[2]);
        $api->saveDefinition($edit);
        permissionAccessProof($this, $codes[3]);
        expect(fn () => $api->applyAssignments($staleApply))->toThrow(OperationRejected::class)
            ->and(fn () => $api->saveDefinition($edit))->toThrow(OperationRejected::class)
            ->and(fn () => $api->deleteDefinition('role', $role['public_id'], 2))->toThrow(OperationRejected::class);
        expect(DB::table('runa_audit.events')->where('action', 'access.refused')->count())->toBeGreaterThanOrEqual(3);
        // The same unconsumed proof remains available after refused mutations.
        $api->deleteDefinition('preset', $preset['public_id'], 1);
        permissionAccessProof($this, $codes[4]);
        $api->deleteDefinition('role', $role['public_id'], 2);
        expect(fn () => $api->definition('role', $role['public_id']))->toThrow(OperationRejected::class);
        $system = DB::table('runa_permissions.roles')->where('system', true)->first();
        permissionAccessProof($this, $codes[5]);
        expect(fn () => $api->deleteDefinition('role', $system->public_id, 1))->toThrow(OperationRejected::class);
        expect($api->definitions('role')['items'])->toHaveCount(1);
        expect(fn () => new DefinitionInput('role', new DictionaryEntry('runa.permissions.super_admin', 'System', 'System', 'System.', 'System.'), new AssignmentSet))->toThrow(InvalidArgumentException::class);
    });
})->group('permissions-access');

it('copies exact ordinary assignments without flattening roles or replacing protected system membership', function (): void {
    $codes = permissionAccessManager($this);
    $source = app(ChangeIdentity::class)->create(new NewIdentity('copy-source@example.test', name: 'Source', active: true, approved: true));
    $target = app(ChangeIdentity::class)->create(new NewIdentity('copy-target@example.test', name: 'Target', active: true, approved: true));
    $read = DB::table('runa_permissions.permissions')->where('code', 'runa.audit.read')->value('public_id');
    ($this->asPermissionUser)(function () use ($codes, $source, $target, $read): void {
        $api = $this->permissionAccess;
        $draft = $api->previewDefinition(permissionDefinition('role', 'copy_reader', [$read]));
        permissionAccessProof($this, $codes[0]);
        $role = $api->saveDefinition($draft);
        $system = DB::table('runa_permissions.roles')->where('system', true)->value('id');
        $roleId = DB::table('runa_permissions.roles')->where('public_id', $role['public_id'])->value('id');
        // Exact fixture-only assignments isolate ordinary-copy semantics from the dedicated writer.
        foreach ([$source->publicId, $target->publicId] as $user) {
            DB::table('runa_permissions.user_roles')->insert(['user_public_id' => $user, 'role_id' => $system, 'created_at' => now(), 'updated_at' => now()]);
        }
        DB::table('runa_permissions.user_roles')->insert(['user_public_id' => $source->publicId, 'role_id' => $roleId, 'created_at' => now(), 'updated_at' => now()]);
        $permissionId = DB::table('runa_permissions.permissions')->where('public_id', $read)->value('id');
        DB::table('runa_permissions.user_permissions')->insert(['user_public_id' => $target->publicId, 'permission_id' => $permissionId, 'created_at' => now(), 'updated_at' => now()]);
        $preview = $api->previewCopy($source->publicId, $target->publicId, 'replace');
        expect($preview->requested->roles)->toBe([$role['public_id']])->and($preview->requested->permissions)->toBe([])
            ->and($preview->removed['permissions'])->toBe([$read]);
        permissionAccessProof($this, $codes[1]);
        $result = $api->applyAssignments($preview);
        expect($result['super_admin'])->toBeTrue()->and($result['assigned']['roles'])->toBe([$role['public_id']])
            ->and($result['assigned']['permissions'])->toBe([]);
        $roleChange = $api->previewDefinition(permissionDefinition('role', 'copy_reader', id: $role['public_id'], version: 1));
        expect($roleChange->affectedUsers)->toBe(2)->and($roleChange->permissionRemoved)->toBe(['runa.audit.read']);
        permissionAccessProof($this, $codes[2]);
        expect(fn () => $api->deleteDefinition('role', $role['public_id'], 1))->toThrow(OperationRejected::class);
    });
})->group('permissions-access');

it('rolls back custom role and preset member writes after their aggregate Audit event fails', function (): void {
    $codes = permissionAccessManager($this);
    $read = DB::table('runa_permissions.permissions')->where('code', 'runa.audit.read')->value('public_id');
    ($this->asPermissionUser)(function () use ($codes, $read): void {
        $api = $this->permissionAccess;
        $role = $api->previewDefinition(permissionDefinition('role', 'rollback', [$read]));
        permissionAccessProof($this, $codes[0]);
        DB::statement("ALTER TABLE runa_audit.events ADD CONSTRAINT access_definition_refusal CHECK (action <> 'role.saved') NOT VALID");
        expect(fn () => $api->saveDefinition($role))->toThrow(AuditUnavailable::class);
        expect(DB::table('runa_permissions.roles')->where('code', 'custom.rollback')->exists())->toBeFalse()
            ->and(DB::table('runa_permissions.role_permissions')->count())->toBe(0)
            ->and(DB::table('runa_auth.step_up_proofs')->where('action', 'runa.permissions.access.manage')->value('consumed_at'))->toBeNull();
        DB::statement('ALTER TABLE runa_audit.events DROP CONSTRAINT access_definition_refusal');
        $saved = $api->saveDefinition($role);
        $preset = $api->previewDefinition(permissionDefinition('preset', 'rollback', roles: [$saved['public_id']]));
        permissionAccessProof($this, $codes[1]);
        DB::statement("ALTER TABLE runa_audit.events ADD CONSTRAINT access_definition_refusal CHECK (action <> 'preset.saved') NOT VALID");
        expect(fn () => $api->saveDefinition($preset))->toThrow(AuditUnavailable::class);
        expect(DB::table('runa_permissions.presets')->count())->toBe(0)->and(DB::table('runa_permissions.preset_roles')->count())->toBe(0);
    });
})->group('permissions-access');

it('denies access writers without native grants and bounds public assignment inputs', function (): void {
    $api = app(ModuleApis::class)->resolve('runa.permissions', PermissionsAccess::class, '1.0.0');
    expect(fn () => $api->definitions('role'))->toThrow(OperationRejected::class);
    ($this->asPermissionUser)(function () use ($api): void {
        expect(fn () => $api->previewAssignments($this->permissionUser->publicId, new AssignmentSet))->toThrow(OperationRejected::class);
        ($this->directPermission)('runa.permissions.introspection.read');
        expect($api->definitions('role')['items'])->toHaveCount(1);
        expect(fn () => $api->definition('other', (string) Str::uuid7()))->toThrow(InvalidArgumentException::class);
    });
    expect(fn () => new AssignmentSet(array_fill(0, 101, (string) Str::uuid7())))->toThrow(InvalidArgumentException::class)
        ->and(fn () => new AssignmentSet(['invalid']))->toThrow(InvalidArgumentException::class);
})->group('permissions-access');

/** Test-only bootstrap; production initialization remains the operator integration's responsibility. */
function permissionSystemMember(string $user): void
{
    $role = DB::table('runa_permissions.roles')->where('system', true)->value('id');
    DB::table('runa_permissions.user_roles')->insert(['role_id' => $role, 'user_public_id' => $user, 'created_at' => now(), 'updated_at' => now()]);
}

it('preserves last eligible authority across lifecycle changes without blocking activation or harmless edits', function (): void {
    $contracts = new ModuleContractSuite(base_path(), true);
    foreach (['runa.auth', 'runa.permissions'] as $module) {
        expect($contracts->runtime(app(ModuleRegistry::class)->snapshot()->modules[$module]))->toBe([]);
    }
    permissionSystemMember($this->permissionUser->publicId);
    app()->bind(AuthorityGuard::class, ProtectedAuthority::class);
    $changes = app(ChangeIdentity::class);
    foreach (['deactivate', 'delete'] as $operation) {
        expect(fn () => $changes->change($this->permissionUser->publicId, 1, new LifecycleChange($operation)))
            ->toThrow(IdentityConflict::class, 'authority_change_refused');
    }
    expect(User::where('public_id', $this->permissionUser->publicId)->value('version'))->toBe(1)
        ->and(DB::table('runa_audit.events')->where('outcome', 'refused')->where('action', 'like', 'identity.%')->count())->toBe(2);
    // Existing authority does not prevent creating a new identity or activating it.
    $other = $changes->create(new NewIdentity('authority-backup@example.test', name: 'Backup'));
    $other = $changes->change($other->publicId, 1, new LifecycleChange('approve'));
    $other = $changes->change($other->publicId, $other->version, new LifecycleChange('activate'));
    permissionSystemMember($other->publicId);
    // A blocked second administrator is not a usable survivor.
    DB::transaction(function () use ($other): void {
        User::where('public_id', $other->publicId)->first()->forceFill(['security_locked' => true])->save();
    });
    expect(fn () => $changes->change($this->permissionUser->publicId, 1, new LifecycleChange('delete')))->toThrow(IdentityConflict::class);
    $changes->change($other->publicId, $other->version, new LifecycleChange('set_security_lock', enabled: false));
    $changes->change($this->permissionUser->publicId, 1, new LifecycleChange('deactivate'));
    expect(User::where('public_id', $this->permissionUser->publicId)->value('active'))->toBeFalse();
    expect(fn () => DB::transaction(fn () => app(AuthorityGuard::class)->assertMayRemove(DB::connection(), [$other->publicId, $this->permissionUser->publicId])))
        ->toThrow(OperationRejected::class);
    $changes->change($this->permissionUser->publicId, 2, new LifecycleChange('activate'));
    expect(User::where('public_id', $this->permissionUser->publicId)->value('active'))->toBeTrue();
    ($this->asPermissionUser)(function (): void {
        $api = app(ModuleApis::class)->resolve('runa.permissions', PermissionsAccess::class, '1.0.0');
        expect($api->assignments($this->permissionUser->publicId)['super_admin'])->toBeTrue();
    });
})->group('permissions-authority');

it('requires dedicated authority grants and real both factor proof while rejecting self escalation and last removal', function (): void {
    $codes = permissionAccessManager($this);
    $target = app(ChangeIdentity::class)->create(new NewIdentity('authority-target@example.test', name: 'Authority target', active: true, approved: true));
    $api = app(ModuleApis::class)->resolve('runa.permissions', PermissionsAuthority::class, '1.0.0');
    ($this->asPermissionUser)(function () use ($codes, $target, $api): void {
        expect(fn () => $api->previewAuthority($target->publicId, true))->toThrow(OperationRejected::class);
        ($this->directPermission)('runa.permissions.authority.manage');
        expect(fn () => $api->previewAuthority($this->permissionUser->publicId, true))->toThrow(OperationRejected::class);
        $preview = $api->previewAuthority($target->publicId, true);
        expect(fn () => $api->applyAuthority($preview))->toThrow(OperationRejected::class);
        permissionAccessProof($this, $codes[0], 'runa.permissions.authority.manage');
        $result = $api->applyAuthority($preview);
        expect($result['super_admin'])->toBeTrue()->and($result['revision'])->toBe(1);
        expect(fn () => $api->applyAuthority($preview))->toThrow(OperationRejected::class);
        $remove = $api->previewAuthority($target->publicId, false);
        permissionAccessProof($this, $codes[1], 'runa.permissions.authority.manage');
        expect(fn () => $api->applyAuthority($remove))->toThrow(OperationRejected::class)
            ->and(DB::table('runa_auth.step_up_proofs')->where('action', 'runa.permissions.authority.manage')->value('consumed_at'))->toBeNull();
        // A second usable member permits removal, and the refused proof was rolled back.
        permissionSystemMember($this->permissionUser->publicId);
        $result = $api->applyAuthority($remove);
        expect($result['super_admin'])->toBeFalse()->and($result['revision'])->toBe(2)
            ->and(DB::table('runa_audit.events')->where('action', 'authority.removed')->count())->toBe(1)
            ->and(DB::table('runa_audit.events')->where('action', 'access.refused')->count())->toBeGreaterThanOrEqual(3);
    });
})->group('permissions-authority');

it('binds force role deletion to practical effects references confirmation and transactional history', function (): void {
    $codes = permissionAccessManager($this);
    ($this->directPermission)('runa.permissions.roles.force_delete');
    $target = app(ChangeIdentity::class)->create(new NewIdentity('force-role@example.test', name: 'Role recipient', active: true, approved: true));
    $read = DB::table('runa_permissions.permissions')->where('code', 'runa.audit.read')->value('public_id');
    $api = app(ModuleApis::class)->resolve('runa.permissions', PermissionsAuthority::class, '1.0.0');
    ($this->asPermissionUser)(function () use ($api, $codes, $target, $read): void {
        $ordinary = $this->permissionAccess;
        permissionAccessProof($this, $codes[0]);
        $role = $ordinary->saveDefinition($ordinary->previewDefinition(permissionDefinition('role', 'force_reader', [$read])));
        permissionAccessProof($this, $codes[1]);
        $preset = $ordinary->saveDefinition($ordinary->previewDefinition(permissionDefinition('preset', 'force_template', roles: [$role['public_id']])));
        permissionAccessProof($this, $codes[2]);
        $ordinary->applyAssignments($ordinary->previewAssignments($target->publicId, new AssignmentSet([$role['public_id']])));
        $preview = $api->previewRoleDeletion($role['public_id'], 1);
        expect($preview->users)->toHaveCount(1)->and($preview->presets)->toHaveCount(1)
            ->and($preview->users[0]['permission_removed'])->toBe(['runa.audit.read']);
        permissionAccessProof($this, $codes[3], 'runa.permissions.roles.force_delete');
        expect(fn () => $api->forceDeleteRole($preview, false))->toThrow(OperationRejected::class);
        // Audit fails only after assignment/preset histories and actual unlinking have occurred.
        DB::statement("ALTER TABLE runa_audit.events ADD CONSTRAINT force_delete_refusal CHECK (action <> 'role.force_deleted') NOT VALID");
        expect(fn () => $api->forceDeleteRole($preview, true))->toThrow(AuditUnavailable::class);
        expect($ordinary->assignments($target->publicId)['assigned']['roles'])->toBe([$role['public_id']])
            ->and($ordinary->definition('preset', $preset['public_id'])['version'])->toBe(1)
            ->and(DB::table('runa_auth.step_up_proofs')->where('action', 'runa.permissions.roles.force_delete')->value('consumed_at'))->toBeNull();
        DB::statement('ALTER TABLE runa_audit.events DROP CONSTRAINT force_delete_refusal');
        $api->forceDeleteRole($preview, true);
        expect($ordinary->assignments($target->publicId)['assigned']['roles'])->toBe([])
            ->and($ordinary->definition('preset', $preset['public_id'])['assigned']['roles'])->toBe([])
            ->and($ordinary->definition('preset', $preset['public_id'])['version'])->toBe(2)
            ->and(DB::table('runa_audit.events')->where('action', 'role.force_deleted')->count())->toBe(1);
        $system = DB::table('runa_permissions.roles')->where('system', true)->first();
        expect(fn () => $api->previewRoleDeletion($system->public_id, 1))->toThrow(OperationRejected::class);
    });
})->group('permissions-authority');

it('refuses stale force deletion after recipient changes and preserves other effective grant sources', function (): void {
    $codes = permissionAccessManager($this);
    ($this->directPermission)('runa.permissions.roles.force_delete');
    $target = app(ChangeIdentity::class)->create(new NewIdentity('force-stale@example.test', name: 'Stale recipient', active: true, approved: true));
    $read = DB::table('runa_permissions.permissions')->where('code', 'runa.audit.read')->value('public_id');
    ($this->asPermissionUser)(function () use ($codes, $target, $read): void {
        $ordinary = $this->permissionAccess;
        $api = app(ModuleApis::class)->resolve('runa.permissions', PermissionsAuthority::class, '1.0.0');
        permissionAccessProof($this, $codes[0]);
        $role = $ordinary->saveDefinition($ordinary->previewDefinition(permissionDefinition('role', 'stale_force', [$read])));
        permissionAccessProof($this, $codes[1]);
        $ordinary->applyAssignments($ordinary->previewAssignments($target->publicId, new AssignmentSet([$role['public_id']])));
        $stale = $api->previewRoleDeletion($role['public_id'], 1);
        permissionAccessProof($this, $codes[2]);
        $ordinary->applyAssignments($ordinary->previewAssignments($target->publicId, new AssignmentSet(permissions: [$read])));
        permissionAccessProof($this, $codes[3], 'runa.permissions.roles.force_delete');
        expect(fn () => $api->forceDeleteRole($stale, true))->toThrow(OperationRejected::class);
        $fresh = $api->previewRoleDeletion($role['public_id'], 1);
        expect($fresh->users[0]['permission_removed'])->toBe([]);
        $api->forceDeleteRole($fresh, true);
        expect($ordinary->assignments($target->publicId)['assigned']['permissions'])->toBe([$read]);
    });
})->group('permissions-authority');

it('audits scheduled last authority refusal without starving unrelated lifecycle work', function (): void {
    permissionSystemMember($this->permissionUser->publicId);
    app()->bind(AuthorityGuard::class, ProtectedAuthority::class);
    $changes = app(ChangeIdentity::class);
    $other = $changes->create(new NewIdentity('scheduled-other@example.test', name: 'Scheduled', active: true, approved: true));
    $at = now('UTC')->addMinute()->format('Y-m-d\TH:i:s\Z');
    foreach ([$this->permissionUser->publicId, $other->publicId] as $id) {
        $changes->change($id, 1, new LifecycleChange('schedule_deactivation', at: $at));
    }
    $this->travel(2)->minutes();
    app(ScheduledLifecycle::class)->run();
    expect(User::where('public_id', $this->permissionUser->publicId)->value('active'))->toBeTrue()
        ->and(User::where('public_id', $other->publicId)->value('active'))->toBeFalse()
        ->and(DB::table('runa_audit.events')->where('action', 'authority.refused')->where('outcome', 'refused')->count())->toBe(1);
    $this->travelBack();
})->group('permissions-authority');

it('serializes two real concurrent last authority removals under the shared transaction lock', function (): void {
    $other = app(ChangeIdentity::class)->create(new NewIdentity('parallel-authority@example.test', name: 'Parallel', active: true, approved: true));
    foreach ([$this->permissionUser->publicId, $other->publicId] as $id) {
        permissionSystemMember($id);
    }
    $workers = [];
    $inputs = [];
    DB::beginTransaction();
    app(AuthorityLock::class)->acquire(DB::connection());
    try {
        foreach ([$this->permissionUser->publicId, $other->publicId] as $id) {
            $input = new InputStream;
            $process = new Process([PHP_BINARY, '../tests/backend/runa/Fixtures/authority-worker.php', $id, '1'], base_path(),
                ['APP_ENV' => 'testing', 'DB_DATABASE' => DB::connection()->getDatabaseName(), 'LARAVEL_STORAGE_PATH' => storage_path()]);
            $process->setTimeout(null);
            $process->setInput($input);
            $process->start();
            $workers[] = $process;
            $inputs[] = $input;
        }
        foreach ($workers as $worker) {
            expect($worker->waitUntil(fn ($type, $output) => str_contains($output, 'ready')))->toBeTrue($worker->getErrorOutput());
        }
        foreach ($inputs as $input) {
            $input->write("go\n");
            $input->close();
        }
        DB::commit();
        $results = [];
        foreach ($workers as $worker) {
            expect($worker->wait())->toBe(0, $worker->getErrorOutput());
            $results[] = trim(str_replace('ready', '', $worker->getOutput()));
        }
        sort($results);
        expect($results)->toBe(['authority_change_refused', 'changed'])
            ->and(User::where('active', true)->count())->toBe(1)
            ->and(DB::table('runa_audit.events')->where('action', 'identity.deactivate')->where('outcome', 'refused')->count())->toBe(1);
    } finally {
        while (DB::connection()->transactionLevel() > 0) {
            DB::rollBack();
        }
        foreach ($workers as $worker) {
            if ($worker->isRunning()) {
                $worker->stop();
            }
        }
    }
})->group('permissions-authority');

it('keeps the last superadministrator active through self service and rolls back consumed proof before refusal history', function (): void {
    $settings = app(ModuleSettings::class)->read('runa.auth');
    app(ModuleSettings::class)->update('runa.auth', ['self_deactivate' => true], $settings['revision'], $settings['generation']);
    permissionAccessManager($this);
    permissionSystemMember($this->permissionUser->publicId);
    app()->bind(AuthorityGuard::class, ProtectedAuthority::class);
    $this->postJson('/api/runa/auth/step-up', ['action' => 'runa.auth.self.deactivate', 'requirement' => 'password',
        'current_password' => 'permission-access-password'])->assertOk();
    $version = User::where('public_id', $this->permissionUser->publicId)->value('version');
    $this->postJson('/api/runa/auth/profile/lifecycle', ['version' => $version, 'operation' => 'deactivate'])
        ->assertConflict()->assertJsonPath('error.code', 'authority_change_refused');
    expect(User::where('public_id', $this->permissionUser->publicId)->value('active'))->toBeTrue()
        ->and(DB::table('runa_auth.step_up_proofs')->where('action', 'runa.auth.self.deactivate')->value('consumed_at'))->toBeNull()
        ->and(DB::table('runa_audit.events')->where('action', 'security.refused')->where('outcome', 'refused')->count())->toBe(1);
})->group('permissions-authority');

it('rolls back dedicated authority membership revision and proof after required grant history fails', function (): void {
    $codes = permissionAccessManager($this);
    ($this->directPermission)('runa.permissions.authority.manage');
    $target = app(ChangeIdentity::class)->create(new NewIdentity('authority-rollback@example.test', name: 'Rollback', active: true, approved: true));
    ($this->asPermissionUser)(function () use ($codes, $target): void {
        $api = app(ModuleApis::class)->resolve('runa.permissions', PermissionsAuthority::class, '1.0.0');
        $preview = $api->previewAuthority($target->publicId, true);
        permissionAccessProof($this, $codes[0], 'runa.permissions.authority.manage');
        DB::statement("ALTER TABLE runa_audit.events ADD CONSTRAINT authority_history_refusal CHECK (action <> 'authority.granted') NOT VALID");
        expect(fn () => $api->applyAuthority($preview))->toThrow(AuditUnavailable::class)
            ->and(DB::table('runa_permissions.user_roles')->where('user_public_id', $target->publicId)->count())->toBe(0)
            ->and(DB::table('runa_permissions.user_access')->where('user_public_id', $target->publicId)->count())->toBe(0)
            ->and(DB::table('runa_auth.step_up_proofs')->where('action', 'runa.permissions.authority.manage')->value('consumed_at'))->toBeNull();
        DB::statement('ALTER TABLE runa_audit.events DROP CONSTRAINT authority_history_refusal');
        expect($api->applyAuthority($preview)['super_admin'])->toBeTrue();
    });
})->group('permissions-authority');

it('integrates first administrator authority once with atomic Audit and resumable invitation admission', function (): void {
    config()->set('queue.default', 'database');
    config()->set('hashing.bcrypt.rounds', 4);
    $accounts = app(OperatorAccounts::class);
    $input = new NewIdentity('initial-integrated@example.test', name: 'Initial administrator');
    DB::statement('ALTER TABLE runa_audit.events ADD CONSTRAINT initial_audit_refusal CHECK (false) NOT VALID');
    expect(fn () => $accounts->initialize($input))->toThrow(AuditUnavailable::class);
    expect(User::where('email', $input->email)->exists())->toBeFalse()
        ->and(DB::table('runa_permissions.user_roles')->count())->toBe(0)
        ->and(DB::table('runa_auth.initialization')->value('completed_at'))->toBeNull();
    DB::statement('ALTER TABLE runa_audit.events DROP CONSTRAINT initial_audit_refusal');
    $material = $accounts->initialize($input);
    $repeat = $accounts->initialize($input);
    expect($repeat['public_id'])->toBe($material['public_id'])
        ->and(DB::table('runa_permissions.user_roles')->count())->toBe(1)
        ->and(DB::table('runa_permissions.user_access')->where('user_public_id', $material['public_id'])->value('version'))->toBe(1)
        ->and(DB::table('runa_audit.events')->where('action', 'authority.initialized')->count())->toBe(1);
    expect(fn () => $accounts->initialize(new NewIdentity('another-initial@example.test', name: 'Other')))->toThrow(RuntimeException::class);
    $accounts->initializeInvitation($input);
    $events = DB::table('runa_audit.events')->count();
    $accounts->initializeInvitation($input);
    expect(DB::table('runa_audit.events')->count())->toBe($events)
        ->and(DB::table('runa_auth.entry_tokens')->where('purpose', 'invitation')->count())->toBe(1);
    $token = DB::table('runa_auth.entry_tokens')->where('purpose', 'invitation')->first();
    $secret = Crypt::decryptString($token->delivery_secret);
    app(EntryTokens::class)->consume($secret, 'invitation', 'integrated-administrator-password');
    $accounts->initializeInvitation($input); // Crash/resume after completed setup is a no-op.
    expect(fn () => $accounts->initialize($input))->toThrow(RuntimeException::class);
    $user = User::where('public_id', $material['public_id'])->firstOrFail();
    DB::transaction(function () use ($user): void {
        $user->active = false;
        $user->security_locked = true;
        $user->save();
    });
    app()->bind(OperationAuthorizer::class, DenyProtectedOperations::class);
    app()->bind(AuthorityGuard::class, DenyAuthorityChanges::class);
    $recovery = $accounts->recover($user->public_id);
    app(Passwords::class)->reset($recovery['token'], 'integrated-recovered-password');
    expect(User::find($user->id)->active)->toBeTrue()->and(User::find($user->id)->security_locked)->toBeFalse();
    expect(DB::table('runa_audit.events')->get()->toJson())->not->toContain($material['token'], $repeat['token'], $secret, $recovery['token']);
    config()->set('auth.operator_recovery_enabled', false);
    expect(fn () => $accounts->recover($user->public_id))->toThrow(RuntimeException::class);
})->group('core-integration');

it('integrates Permissions selection grants with Auth bulk proof and rolls back failed history', function (): void {
    $codes = permissionAccessManager($this);
    $target = app(ChangeIdentity::class)->create(new NewIdentity('selected-bulk@example.test', name: 'Selected', active: true, approved: true));
    $selection = new SessionSelection([$target->publicId]);
    $api = app(ModuleApis::class)->resolve('runa.auth', AuthBulkSessions::class, '1.0.0');
    ($this->directPermission)('runa.auth.sessions.bulk_revoke');
    ($this->asPermissionUser)(function () use ($selection, $api, $codes): void {
        expect(fn () => $api->preview($selection))->toThrow(OperationRejected::class);
        ($this->directPermission)('runa.permissions.users.select');
        $preview = $api->preview($selection);
        expect(count($preview['accounts']))->toBe(1);
        expect(fn () => $api->revoke($selection, false, $preview['fingerprint']))->toThrow(OperationRejected::class);
        permissionAccessProof($this, $codes[0], 'runa.auth.sessions.bulk_revoke');
        DB::statement('ALTER TABLE runa_audit.events ADD CONSTRAINT selection_audit_refusal CHECK (false) NOT VALID');
        expect(fn () => $api->revoke($selection, false, $preview['fingerprint']))->toThrow(AuditUnavailable::class);
        expect(DB::table('runa_auth.step_up_proofs')->where('action', 'runa.auth.sessions.bulk_revoke')->value('consumed_at'))->toBeNull();
        DB::statement('ALTER TABLE runa_audit.events DROP CONSTRAINT selection_audit_refusal');
        expect($api->revoke($selection, false, $preview['fingerprint'])['accounts'])->toBe(1)
            ->and(DB::table('runa_audit.events')->where('action', 'sessions.bulk_revoked')->value('actor_label'))->toBe('Permission fixture');
        DB::table('runa_permissions.user_permissions')->where('user_public_id', $this->permissionUser->publicId)
            ->where('permission_id', DB::table('runa_permissions.permissions')->where('code', 'runa.permissions.users.select')->value('id'))->delete();
        expect(fn () => $api->preview($selection))->toThrow(OperationRejected::class);
    });
})->group('core-integration');

it('projects safe Core management and resolves historical Auth identity without rewriting Audit', function (): void {
    foreach (['runa.audit' => ['event'], 'runa.auth' => ['policy', 'session', 'user'], 'runa.permissions' => ['assignment', 'authority', 'preset', 'role']] as $module => $keys) {
        $record = app(ModuleRegistry::class)->snapshot()->modules[$module];
        expect((new ModuleContractSuite(base_path(), true))->runtime($record))->toBe([]);
        $view = app(ModuleManagement::class)->read($module);
        expect($view->diagnostics)->toBe([])->and(array_keys($view->entities))->toBe($keys);
        $json = json_encode($view->entities, JSON_THROW_ON_ERROR);
        expect($json)->not->toContain('totp_secret', 'password_history', 'token_hash', 'recovery_hashes');
    }
    ($this->directPermission)('runa.auth.identities.read');
    ($this->directPermission)('runa.audit.read');
    $lastId = DB::table('runa_audit.events')->max('id');
    $historyBefore = DB::table('runa_audit.events')->where('id', '<=', $lastId)->orderBy('id')->get()->toJson();
    DB::transaction(function (): void {
        User::where('public_id', $this->permissionUser->publicId)->update(['name' => 'Historical name', 'deleted_at' => now(), 'active' => false]);
    });
    // Historical subjects do not need to be eligible; only the authorized reader does.
    $reader = app(ChangeIdentity::class)->create(new NewIdentity('history-reader@example.test', name: 'Reader', active: true, approved: true));
    $id = DB::table('runa_permissions.permissions')->where('code', 'runa.auth.identities.read')->value('id');
    DB::table('runa_permissions.user_permissions')->insert(['user_public_id' => $reader->publicId, 'permission_id' => $id, 'created_at' => now(), 'updated_at' => now()]);
    app(PrincipalScope::class)->run(new TrustedPrincipal($reader->publicId), function (): void {
        $identity = app(ModuleApis::class)->resolve('runa.auth', AuthIdentities::class, '1.0.0')->identity($this->permissionUser->publicId);
        expect($identity->deleted)->toBeTrue()->and($identity->displayName)->toBe('Historical name');
    });
    expect(DB::table('runa_audit.events')->where('action', 'identity.created')->count())->toBe(2)
        ->and(DB::table('runa_audit.events')->where('id', '<=', $lastId)->orderBy('id')->get()->toJson())->toBe($historyBefore);
})->group('core-integration');

it('serializes competing first administrator recipients without duplicate authority', function (): void {
    $workers = [];
    $inputs = [];
    DB::beginTransaction();
    app(AuthorityLock::class)->acquire(DB::connection());
    try {
        foreach (['first-parallel@example.test', 'second-parallel@example.test'] as $id) {
            $input = new InputStream;
            $process = new Process([PHP_BINARY, '../tests/backend/runa/Fixtures/initialization-worker.php', $id], base_path(),
                ['APP_ENV' => 'testing', 'DB_DATABASE' => DB::connection()->getDatabaseName(), 'LARAVEL_STORAGE_PATH' => storage_path()]);
            $process->setTimeout(null);
            $process->setInput($input);
            $process->start();
            $workers[] = $process;
            $inputs[] = $input;
        }
        foreach ($workers as $worker) {
            expect($worker->waitUntil(fn ($type, $output) => str_contains($output, 'ready')))->toBeTrue($worker->getErrorOutput());
        }
        foreach ($inputs as $input) {
            $input->write("go\n");
            $input->close();
        }
        DB::commit();
        $results = [];
        foreach ($workers as $worker) {
            expect($worker->wait())->toBe(0, $worker->getErrorOutput());
            $results[] = trim(str_replace('ready', '', $worker->getOutput()));
        }
        sort($results);
        expect($results)->toBe(['initialized', 'refused'])
            ->and(DB::table('runa_permissions.user_roles')->count())->toBe(1)
            ->and(DB::table('runa_audit.events')->where('action', 'authority.initialized')->count())->toBe(1)
            ->and(User::whereIn('email', ['first-parallel@example.test', 'second-parallel@example.test'])->count())->toBe(1);
    } finally {
        while (DB::connection()->transactionLevel() > 0) {
            DB::rollBack();
        }
        foreach ($workers as $worker) {
            if ($worker->isRunning()) {
                $worker->stop();
            }
        }
    }
})->group('core-integration-concurrency');

it('seeds module development corpus idempotently and preserves private credentials with real authority', function (): void {
    app()->bind(AuthorityGuard::class, ProtectedAuthority::class);
    config()->set('queue.default', 'database');
    expect(Artisan::call('runa:seed:plan', ['--modules' => true]))->toBe(0);
    expect(DB::table('runa_auth.development_fixtures')->count())->toBe(0);
    expect(Artisan::call('runa:seed:development', ['--modules' => true]))->toBe(0);
    $accounts = app(DevelopmentFixtures::class)->contributor('runa.auth');
    expect($accounts)->toBeInstanceOf(DevelopmentAccounts::class);
    $rows = array_column($accounts->accounts(), null, 'scenario');
    $admin = User::where('public_id', $rows['administrator']['public_id'])->firstOrFail();
    expect($admin->password)->toBeNull()->and($rows['administrator']['setup_required'])->toBeTrue();
    $roleId = DB::table('runa_permissions.roles')->where('code', 'runa.permissions.super_admin')->value('id');
    expect(DB::table('runa_permissions.user_roles')->where('role_id', $roleId)->pluck('user_public_id')->all())->toBe([$admin->public_id]);
    $password = 'Local!'.bin2hex(random_bytes(15));
    expect($accounts->setup('administrator', $password))->toBeTrue();
    $hash = $admin->fresh()->password;
    expect(Hash::check($password, $hash))->toBeTrue();
    expect($admin->fresh()->email_verified_at)->not->toBeNull();
    expect($accounts->setup('administrator', 'Different!'.bin2hex(random_bytes(15))))->toBeFalse();
    $events = DB::table('runa_audit.events')->count();
    $count = DB::table('runa_auth.users')->count();
    expect(Artisan::call('runa:seed:development', ['--modules' => true]))->toBe(0);
    expect(DB::table('runa_auth.users')->count())->toBe($count)->and($admin->fresh()->password)->toBe($hash)
        ->and(DB::table('runa_audit.events')->count())->toBe($events);
    $reader = $rows['reader']['public_id'];
    expect(DB::table('runa_permissions.user_roles')->where('user_public_id', $reader)->count())->toBe(1);
    expect(DB::table('runa_permissions.user_permissions')->where('user_public_id', $rows['direct']['public_id'])->count())->toBe(1);
    expect(DB::table('runa_permissions.presets')->where('code', 'custom.dev_reader')->count())->toBe(1);
    expect(User::where('public_id', $rows['second_factor']['public_id'])->firstOrFail()->email_factor)->toBeTrue();
    expect(User::where('public_id', $rows['unverified']['public_id'])->firstOrFail()->email_verified_at)->toBeNull();
    $listed = Artisan::call('runa:seed:accounts');
    expect($listed)->toBe(0)->and(Artisan::output())->toContain('dev.administrator@example.test')->not->toContain($hash)->not->toContain($password);
    expect(Artisan::call('runa:seed:accounts', ['scenario' => 'administrator', '--setup' => true, '--no-interaction' => true]))->toBe(1);
    expect(app(PrincipalScope::class)->current())->toBeNull();
});

it('rolls back development provenance accounts and grants when mandatory Audit fails', function (): void {
    app()->bind(AuthorityGuard::class, ProtectedAuthority::class);
    config()->set('queue.default', 'database');
    $fixtures = app(DevelopmentFixtures::class);
    $accounts = $fixtures->contributor('runa.auth');
    $accounts->populate();
    $before = DB::table('runa_audit.events')->count();
    DB::statement("ALTER TABLE runa_audit.events ADD CONSTRAINT reject_development_assignments CHECK (action <> 'development.assignments_created')");
    try {
        expect(fn () => $fixtures->contributor('runa.permissions')->populate())->toThrow(AuditUnavailable::class);
        expect(DB::table('runa_permissions.development_fixtures')->count())->toBe(0)
            ->and(DB::table('runa_permissions.roles')->where('code', 'custom.dev_reader')->count())->toBe(0)
            ->and(DB::table('runa_audit.events')->count())->toBe($before);
    } finally {
        DB::statement('ALTER TABLE runa_audit.events DROP CONSTRAINT reject_development_assignments');
    }
    $admin = collect($accounts->accounts())->firstWhere('scenario', 'administrator');
    DB::statement("ALTER TABLE runa_audit.events ADD CONSTRAINT reject_development_password CHECK (action <> 'development.password_initialized')");
    try {
        expect(fn () => $accounts->setup('administrator', 'Local!'.bin2hex(random_bytes(15))))->toThrow(AuditUnavailable::class);
        expect(User::where('public_id', $admin['public_id'])->firstOrFail()->password)->toBeNull();
    } finally {
        DB::statement('ALTER TABLE runa_audit.events DROP CONSTRAINT reject_development_password');
    }
});

it('refuses missing cyclic or foreign module development contributors before mutation', function (): void {
    $fixtures = app(DevelopmentFixtures::class);
    config()->set('seeding.contributors', ['runa.permissions' => Access::class]);
    expect(fn () => $fixtures->graph([])->plan('development', 'testing'))->toThrow(RuntimeException::class, 'explicitly registered');
    expect(DB::table('runa_auth.development_fixtures')->count())->toBe(0);
    config()->set('seeding.contributors', ['runa.auth' => Access::class]);
    expect(fn () => $fixtures->registered())->toThrow(InvalidArgumentException::class);
    $class = AuthDevelopmentSeeder::class;
    expect(fn () => (new SeedGraph([$class], [$class => [$class]]))->plan('development', 'testing'))->toThrow(RuntimeException::class, 'cycle');
    expect(DB::table('runa_auth.development_fixtures')->count())->toBe(0);
    config()->set('seeding.contributors', ['runa.auth' => Accounts::class]);
    app()->instance(Accounts::class, new class implements DevelopmentContributor
    {
        public function dependsOn(): array
        {
            return ['runa.auth'];
        }

        public function seeders(): array
        {
            return [];
        }
    });
    expect(fn () => $fixtures->graph([]))->toThrow(RuntimeException::class, 'contributor dependency cycle');
    expect(DB::table('runa_auth.development_fixtures')->count())->toBe(0);
});

it('keeps development fixture count tuning independent and refuses adoption of existing accounts', function (): void {
    app()->bind(AuthorityGuard::class, ProtectedAuthority::class);
    config()->set('queue.default', 'database');
    $key = 'seeding.development.'.AuthDevelopmentSeeder::class;
    config()->set($key.'.counts.extra_users', 0);
    $accounts = app(DevelopmentFixtures::class)->contributor('runa.auth');
    $accounts->populate();
    expect(collect($accounts->accounts())->filter(fn ($row) => str_starts_with($row['scenario'], 'extra_')))->toHaveCount(0);
    config()->set($key.'.counts.extra_users', 2);
    $accounts->populate();
    expect(collect($accounts->accounts())->filter(fn ($row) => str_starts_with($row['scenario'], 'extra_')))->toHaveCount(2);
    $admin = collect($accounts->accounts())->firstWhere('scenario', 'administrator');
    DB::table('runa_auth.development_fixtures')->where('scenario', 'administrator')->delete();
    expect(fn () => $accounts->populate())->toThrow(RuntimeException::class);
    expect(DB::table('runa_auth.development_fixtures')->where('scenario', 'administrator')->exists())->toBeFalse();
    expect(User::where('public_id', $admin['public_id'])->count())->toBe(1);
});

it('initializes a fixture credential through hidden console confirmation without exposing it', function (): void {
    app()->bind(AuthorityGuard::class, ProtectedAuthority::class);
    config()->set('queue.default', 'database');
    app(DevelopmentFixtures::class)->contributor('runa.auth')->populate();
    $password = 'Local!'.bin2hex(random_bytes(15));
    $tester = new CommandTester(Artisan::all()['runa:seed:accounts']);
    $tester->setInputs([$password, $password]);
    expect($tester->execute(['scenario' => 'administrator', '--setup' => true], ['interactive' => true]))->toBe(0);
    expect($tester->getDisplay())->toContain('Initial password saved.')->not->toContain($password);
    expect(Hash::check($password, User::where('login', 'dev.administrator')->firstOrFail()->password))->toBeTrue();
});

it('integrates real Core HTTP session CSRF profile and logout without authority substitutes', function (): void {
    app()->bind(AuthorityGuard::class, ProtectedAuthority::class);
    app()->bind(ValidateCsrfToken::class, fn ($app) => new class($app, $app['encrypter']) extends ValidateCsrfToken
    {
        protected function runningUnitTests()
        {
            return false;
        }
    });
    // Rebuild source-bound routes after explicit fixture initialization, as a fresh
    // application boot does; retain document and ordinary application routes.
    $routes = new RouteCollection;
    foreach (app('router')->getRoutes() as $route) {
        if (! isset($route->defaults['_runa_module'])) {
            $routes->add($route);
        }
    }
    app('router')->setRoutes($routes);
    app(ModuleHttp::class)->register();
    app('router')->getRoutes()->refreshNameLookups();
    config()->set('hashing.bcrypt.rounds', 4);
    $password = 'core-http-test-password';
    DB::transaction(function () use ($password): void {
        $user = User::where('public_id', $this->permissionUser->publicId)->firstOrFail();
        $user->password = Hash::make($password);
        $user->save();
    });
    $this->getJson('/api/runa/auth/profile')->assertUnauthorized();
    $this->getJson('/api/runa/auth/csrf')->assertOk()->assertCookie('XSRF-TOKEN');
    $this->withCredentials()->withCookie('runa_session', app('session')->getId());
    $credentials = ['identifier' => 'permission-fixture@example.test', 'password' => $password];
    $this->postJson('/api/runa/auth/login', $credentials)->assertStatus(419);
    $this->withHeader('X-CSRF-TOKEN', app('session')->token());
    $this->postJson('/api/runa/auth/login', $credentials)->assertOk()
        ->assertJsonPath('data.public_id', $this->permissionUser->publicId)
        ->assertJsonPath('data.restrictions', []);
    $this->withCookie('runa_session', app('session')->getId());
    $this->getJson('/api/runa/auth/profile')->assertOk()
        ->assertJsonPath('data.public_id', $this->permissionUser->publicId);
    expect(DB::table('runa_permissions.user_roles')->count())->toBe(0);
    // A logout request without a CSRF proof cannot revoke the session.
    $this->withoutHeader('X-CSRF-TOKEN');
    $this->postJson('/api/runa/auth/logout')->assertStatus(419);
    $this->getJson('/api/runa/auth/session')->assertOk();
    $this->withHeader('X-CSRF-TOKEN', app('session')->token());
    $this->postJson('/api/runa/auth/logout')->assertOk();
    Auth::forgetGuards();
    $this->withCookie('runa_session', app('session')->getId());
    $this->getJson('/api/runa/auth/profile')->assertUnauthorized();
    $this->getJson('/api/runa/auth/session')->assertUnauthorized();
    expect(app(PrincipalScope::class)->current())->toBeNull();
    expect(DB::table('runa_audit.events')->get()->toJson())->not->toContain($password);
})->group('core-integration');

it('serializes permission members of multiple roles as JSON lists', function (): void {
    $codes = permissionAccessManager($this);
    $read = DB::table('runa_permissions.permissions')->where('code', 'runa.audit.read')->value('public_id');
    ($this->asPermissionUser)(function () use ($codes, $read): void {
        $roles = [];
        foreach (['first_reader', 'second_reader'] as $index => $code) {
            $preview = $this->permissionAccess->previewDefinition(permissionDefinition('role', $code, [$read]));
            permissionAccessProof($this, $codes[$index]);
            $roles[] = $this->permissionAccess->saveDefinition($preview)['public_id'];
        }
        $references = app(AccessState::class)->references(new AssignmentSet($roles));
        expect($references['roles'])->toHaveCount(2);
        $decoded = json_decode(json_encode($references, JSON_THROW_ON_ERROR), flags: JSON_THROW_ON_ERROR);
        foreach ($references['roles'] as $index => $role) {
            expect(array_is_list($role['permissions']))->toBeTrue()
                ->and($role['permissions'][0]['public_id'])->toBe($read)
                ->and($decoded->roles[$index]->permissions)->toBeArray();
        }
    });
})->group('permissions-access');
