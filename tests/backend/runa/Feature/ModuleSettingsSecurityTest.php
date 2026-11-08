<?php

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Database\Connection;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\MutationProof;
use Runa\Contracts\Modules\OperationAuthorizer;
use Runa\Contracts\Modules\OperationContext;
use Runa\Contracts\Modules\SettingsAccess;
use Runa\Contracts\Modules\SettingsActor;
use Runa\Contracts\Modules\SettingsParticipant;
use Runa\Contracts\Modules\SettingsPublication;
use Runa\Contracts\Modules\TransactionSecurity;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\ModuleFramework\Api\ModuleDeclarations;
use Runa\ModuleFramework\Api\OperationGuard;
use Runa\ModuleFramework\Api\OperationScope;
use Runa\ModuleFramework\Api\PrincipalScope;
use Runa\ModuleFramework\Data\AuthorityLock;
use Runa\ModuleFramework\HealthDeadline;
use Runa\ModuleFramework\HealthPolicies;
use Runa\ModuleFramework\LifecyclePolicies;
use Runa\ModuleFramework\ModuleAutoloader;
use Runa\ModuleFramework\ModuleAvailability;
use Runa\ModuleFramework\ModuleDiscovery;
use Runa\ModuleFramework\ModuleHealth;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\ModuleFramework\Settings\SettingsDefinitions;
use Runa\ModuleFramework\Settings\SettingsSecurity;
use Runa\ModuleFramework\Settings\SettingTypes;
use Runa\ModuleFramework\Transactions\HealthAdmissions;
use Runa\Runtime\MaintenanceState;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Settings\ModuleSettings;
use Tests\Fixtures\ModuleSources;
use Tests\Fixtures\SettingsCalls;

beforeEach(function (): void {
    SettingsCalls::reset();
    DB::statement('CREATE TEMPORARY TABLE settings_test_evidence (key text PRIMARY KEY, value text NOT NULL)');
    $this->sources = new ModuleSources;
    $code = 'secure_settings'.bin2hex(random_bytes(6));
    $this->module = 'app.'.$code;
    $namespace = 'App\\Modules\\'.str_replace(' ', '', ucwords(str_replace('_', ' ', $code)));
    $manifest = $this->sources->add('app', $code);
    $manifest['declarations']->settings = [$namespace.'\\Definitions\\Settings'];
    $this->sources->write('app', $code, $manifest);
    $stub = file_get_contents(__DIR__.'/../Fixtures/Settings/Settings.php.stub');
    $stub = str_replace('implements SettingsProvider,', 'implements \\Runa\\Contracts\\Modules\\SettingsAccessProvider, SettingsProvider,', $stub);
    $stub = str_replace('public function settings(): array', 'public function access(): \\Runa\\Contracts\\Modules\\SettingsAccess { return SettingsCalls::$access; } public function settings(): array', $stub);
    file_put_contents($this->sources->root.'/app/modules/'.$code.'/backend/Definitions/Settings.php', str_replace('MODULE_NAMESPACE', $namespace, $stub));
    $this->coreRows = DB::table('shared.modules')->where('module_id', 'like', 'runa.%')->get()->all();
    foreach ($this->coreRows as $row) {
        if ($row->installed_version !== null) {
            throw new RuntimeException('Settings security fixture requires absent installed Core storage.');
        }
    }
    // Source-only stand-ins isolate the framework protocol. Real Auth proof and
    // Audit integration remain covered by their owning suites and point 10b.
    foreach (['auth', 'audit'] as $core) {
        $definition = $this->sources->add('runa', $core, 'core');
        $fixtureClass = 'SettingsFixture'.bin2hex(random_bytes(6));
        $definition['declarations']->lifecycle = ['Runa\\Modules\\'.ucfirst($core).'\\Definitions\\'.$fixtureClass];
        $this->sources->write('runa', $core, $definition);
        file_put_contents($this->sources->root.'/runa/modules/'.$core.'/backend/Definitions/'.$fixtureClass.'.php', '<?php namespace Runa\\Modules\\'.ucfirst($core).'\\Definitions; final class '.$fixtureClass.' extends \\Tests\\Fixtures\\TransitionPolicy {}');
    }
    file_put_contents($this->sources->root.'/app/modules/'.$code.'/backend/Definitions/WorkPolicy.php', '<?php namespace '.$namespace.'\\Definitions; final class WorkPolicy extends \\Tests\\Fixtures\\TransitionPolicy {}');
    $db = DB::connection();
    $this->registry = new ModuleRegistry($db, new ModuleDiscovery($this->sources->root));
    $loader = new ModuleAutoloader($this->sources->root, $this->registry, true);
    $this->admissions = new HealthAdmissions($db);
    $health = new ModuleHealth($this->admissions, $db, $this->registry, new HealthPolicies($this->sources->root, app(), $loader, true), new HealthDeadline);
    $availability = new ModuleAvailability($this->registry, $health, new LifecyclePolicies($this->sources->root, app(), $loader, true), '0.4.0');
    $this->grants = new class implements OperationAuthorizer
    {
        public array $denied = [];

        public function allows(OperationContext $context, string $permission): bool
        {
            return ! in_array($permission, $this->denied, true);
        }
    };
    $guard = new OperationGuard($this->registry, $availability, new MaintenanceState(app(), $db, $this->registry, $availability), $this->grants, app(), new OperationScope);
    $this->principal = new TrustedPrincipal((string) Str::uuid7());
    $this->proofs = new class($this->module) implements TransactionSecurity
    {
        public int $consumed = 0;

        public int $lockLevel = 1;

        public function __construct(private string $key) {}

        public function lockSubjects(array $publicIds): void
        {
            expect(DB::connection()->transactionLevel())->toBe($this->lockLevel);
        }

        public function proof(MutationProof $proof, bool $consume): void
        {
            expect(DB::connection()->transactionLevel())->toBe(1);
            if (! DB::table('settings_test_evidence')->where('key', $this->key)->exists()) {
                throw new OperationRejected('step_up_required', 'runa.auth', 'step_up.assert', 403);
            }
            if ($consume) {
                $this->consumed++;
                DB::table('settings_test_evidence')->where('key', $this->key)->delete();
            }
        }
    };
    $this->audit = new class implements SettingsParticipant
    {
        public bool $fail = false;

        public function publishing(Connection $connection, SettingsPublication $publication): void
        {
            if ($this->fail) {
                throw new RuntimeException('Required Audit refused.');
            }
        }
    };
    $this->definitions = new SettingsDefinitions(new ModuleDeclarations($this->sources->root, app(), $loader, true), new SettingTypes, app(Factory::class));
    $security = new SettingsSecurity($db, $this->registry, $guard, app(PrincipalScope::class), new AuthorityLock, $this->proofs, $this->admissions, app());
    $this->settings = new ModuleSettings($db, $this->registry, $this->definitions, app(Encrypter::class), new SettingsCalls, $this->audit, $security);
    SettingsCalls::$access = new SettingsAccess(new ApiOperation('settings.read', 'settings.read'), new ApiOperation('settings.write', 'settings.write'),
        readFields: ['token' => 'settings.secret.read'], writeFields: ['token' => 'settings.secret.write'], proof: new MutationProof('app.settings.publish', 'password'));
    $this->registry->synchronize('runa');
    $this->registry->synchronize('app');
    DB::table('shared.modules')->where('module_id', $this->module)->update(['enabled' => true]);
    DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    $this->update = function (array $patch, ?SettingsActor $actor = null) {
        $state = $this->settings->read($this->module);

        return app(PrincipalScope::class)->run($this->principal, fn () => $this->settings->update($this->module, $patch, $state['revision'], $state['review_id'], $actor));
    };
    $this->issueProof = fn () => DB::table('settings_test_evidence')->insert(['key' => $this->module, 'value' => 'proof']);
});

afterEach(function (): void {
    DB::connection()->rollBack(0);
    DB::table('settings_test_evidence')->where('key', 'like', $this->module.'%')->delete();
    DB::table('shared.module_transitions')->where('target', $this->module)->delete();
    DB::table('shared.modules')->where('module_id', $this->module)->delete();
    $original = array_column($this->coreRows, 'module_id');
    DB::table('shared.modules')->where('module_id', 'like', 'runa.%')->whereNotIn('module_id', $original)->delete();
    foreach ($this->coreRows as $row) {
        DB::table('shared.modules')->where('id', $row->id)->update((array) $row);
    }
    DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    $this->sources->remove();
    SettingsCalls::reset();
});

it('publishes with current field grants and one proof deriving the human actor', function (): void {
    ($this->issueProof)();
    expect(($this->update)(['token' => 'private-value'])->status)->toBe('succeeded');
    expect($this->proofs->consumed)->toBe(1);
    expect(SettingsCalls::$contexts[0]->actor->id)->toBe($this->principal->publicId);
    expect(SettingsCalls::$contexts[0]->changes['token'])->toBe(['replaced' => true]);
    expect(fn () => ($this->update)(['label' => 'again']))->toThrow(OperationRejected::class);
    expect($this->settings->read($this->module)['revision'])->toBe(1);
});

it('rolls back proof publication and source history together when Audit refuses', function (): void {
    ($this->issueProof)();
    $this->audit->fail = true;
    SettingsCalls::$publish = function (Connection $connection): void {
        $connection->table('settings_test_evidence')->insert(['key' => $this->module.'.history', 'value' => 'published']);
    };
    expect(fn () => ($this->update)(['label' => 'candidate']))->toThrow(RuntimeException::class, 'Required Audit refused.');
    expect(DB::table('settings_test_evidence')->where('key', $this->module)->exists())->toBeTrue();
    expect(DB::table('settings_test_evidence')->where('key', $this->module.'.history')->exists())->toBeFalse();
    expect($this->settings->read($this->module)['revision'])->toBe(0);
    expect(DB::table('shared.module_transitions')->where('target', $this->module)->exists())->toBeFalse();
});

it('refuses missing proof forbidden fields spoofed actors and grants lost inside publication', function (): void {
    expect(fn () => ($this->update)(['label' => 'candidate']))->toThrow(OperationRejected::class);
    ($this->issueProof)();
    $this->grants->denied = ['settings.secret.write'];
    expect(fn () => ($this->update)(['token' => 'private']))->toThrow(OperationRejected::class);
    $this->grants->denied = [];
    expect(fn () => ($this->update)(['label' => 'candidate'], new SettingsActor('system', 'operator')))->toThrow(OperationRejected::class);
    SettingsCalls::$authorize = function (): void {
        $this->grants->denied = ['settings.write'];
    };
    expect(fn () => ($this->update)(['label' => 'candidate']))->toThrow(OperationRejected::class);
    expect(DB::table('settings_test_evidence')->where('key', $this->module)->exists())->toBeTrue();
    expect($this->settings->read($this->module)['revision'])->toBe(0);
});

it('filters unauthorized read fields and rejects undeclared access keys', function (): void {
    $this->grants->denied = ['settings.secret.read'];
    $view = app(PrincipalScope::class)->run($this->principal, fn () => $this->settings->readAuthorized($this->module));
    expect($view['settings'])->toHaveKey('label')->not->toHaveKey('token');
    expect($this->settings->read($this->module)['settings']['token'])->toHaveKey('set')->not->toHaveKey('value');
    SettingsCalls::$access = new SettingsAccess(new ApiOperation('settings.read', 'settings.read'), new ApiOperation('settings.write', 'settings.write'), writeFields: ['missing' => 'settings.write']);
    expect(fn () => $this->settings->read($this->module))->toThrow(RuntimeException::class, 'Invalid settings declaration or defaults.');
});

it('joins an admitted read while refusing unrelated nested transactions and preserving field grants', function (): void {
    $this->grants->denied = ['settings.secret.read'];
    app(PrincipalScope::class)->run($this->principal, function (): void {
        expect(fn () => DB::transaction(fn () => $this->settings->readAuthorized($this->module)))
            ->toThrow(RuntimeException::class, 'Standalone health admission requires an independent transaction boundary.');
        expect(DB::connection()->transactionLevel())->toBe(0);
        $this->proofs->lockLevel = 2;
        $view = DB::transaction(function (): array {
            $result = $this->admissions->read([], DB::connection(), fn () => $this->settings->readAuthorized($this->module));
            expect(DB::connection()->transactionLevel())->toBe(1);

            return $result;
        });
        expect($view['settings'])->toHaveKey('label')->not->toHaveKey('token');
        expect(DB::connection()->transactionLevel())->toBe(0);
    });
});

it('keeps trusted operator publication separate from authenticated proof and refuses missing HTTP identity', function (): void {
    $state = $this->settings->read($this->module);
    expect($this->settings->update($this->module, ['label' => 'operator'], $state['revision'], $state['review_id'], new SettingsActor('system', 'maintenance'))->status)->toBe('succeeded');
    expect($this->proofs->consumed)->toBe(0);
    $route = new Route('POST', '/settings-fixture', fn () => null);
    app('request')->setRouteResolver(fn () => $route);
    $state = $this->settings->read($this->module);
    expect(fn () => $this->settings->update($this->module, ['label' => 'http'], $state['revision'], $state['review_id'], new SettingsActor('system', 'spoof')))->toThrow(OperationRejected::class);
});

it('refuses a changed default connection before consuming proof or publishing settings', function (): void {
    ($this->issueProof)();
    $default = DB::getDefaultConnection();
    config(['database.connections.settings_changed' => config('database.connections.'.$default)]);
    SettingsCalls::$authorize = fn () => DB::setDefaultConnection('settings_changed');
    try {
        expect(fn () => ($this->update)(['label' => 'candidate']))->toThrow(OperationRejected::class);
    } finally {
        DB::setDefaultConnection($default);
        DB::purge('settings_changed');
    }
    expect($this->proofs->consumed)->toBe(0);
    expect($this->settings->read($this->module)['revision'])->toBe(0);
    expect(DB::table('settings_test_evidence')->where('key', $this->module)->exists())->toBeTrue();
});

it('rejects a stale reviewed revision before consuming its one-use proof', function (): void {
    $reviewed = $this->settings->read($this->module);
    $this->settings->update($this->module, ['label' => 'operator change'], $reviewed['revision'], $reviewed['review_id']);
    ($this->issueProof)();
    expect(fn () => app(PrincipalScope::class)->run($this->principal, fn () => $this->settings->update($this->module, ['label' => 'stale draft'], $reviewed['revision'], $reviewed['review_id'])))->toThrow(RuntimeException::class, 'Settings revision changed or transition unresolved.');
    expect($this->proofs->consumed)->toBe(0);
    expect($this->settings->read($this->module)['settings']['label']['value'])->toBe('operator change');
    expect(DB::table('settings_test_evidence')->where('key', $this->module)->exists())->toBeTrue();
});
