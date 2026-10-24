<?php

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
use Runa\Modules\Permissions\Contracts\Permissions;
use Runa\Modules\Permissions\Support\DeclarationCatalog;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\ModuleDeclarations;
use Runa\Support\Modules\Api\OperationGuard;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Api\PrincipalScope;
use Runa\Support\Modules\Data\AuthorityLock;
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
