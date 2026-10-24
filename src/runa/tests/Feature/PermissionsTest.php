<?php

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
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
use Runa\Modules\Audit\Contracts\AuditUnavailable;
use Runa\Modules\Audit\Storage\AppendEvents;
use Runa\Modules\Auth\Actions\ChangeIdentity;
use Runa\Modules\Auth\Contracts\NewIdentity;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Permissions\Actions\SynchronizeDeclarations;
use Runa\Modules\Permissions\Contracts\AssignmentSet;
use Runa\Modules\Permissions\Contracts\DefinitionInput;
use Runa\Modules\Permissions\Contracts\Permissions;
use Runa\Modules\Permissions\Contracts\PermissionsAccess;
use Runa\Modules\Permissions\Support\DeclarationCatalog;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\ModuleDeclarations;
use Runa\Support\Modules\Api\ModuleHttp;
use Runa\Support\Modules\Api\OperationGuard;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Api\PrincipalScope;
use Runa\Support\Modules\Data\AuthorityLock;
use Runa\Support\Modules\Management\ModuleManagement;
use Runa\Support\Modules\ModuleAutoloader;
use Runa\Support\Modules\ModuleDiscovery;
use Runa\Support\Modules\ModuleRegistry;
use Runa\Support\Modules\Settings\ModuleSettings;
use Runa\Support\Modules\Updates\ModuleUpdates;
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
        DB::table('shared.modules')->where('module_id', 'runa.audit')->update(['enabled' => false]);
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

function permissionAccessProof($test, string $code): void
{
    $test->postJson('/api/runa/auth/step-up', ['action' => 'runa.permissions.access.manage', 'requirement' => 'both',
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
            ->and(array_keys($management->entities))->toBe(['assignment', 'preset', 'role']);
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
        // Exact fixture-only privileged assignments; no public system-role writer is introduced.
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
