<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Runa\ModuleFramework\Api\ModuleDeclarations;
use Runa\ModuleFramework\Api\ModuleHttp;
use Runa\ModuleFramework\Console\ModuleConsole;
use Runa\ModuleFramework\Events\EventDefinitions;
use Runa\ModuleFramework\HealthPolicies;
use Runa\ModuleFramework\LifecyclePolicies;
use Runa\ModuleFramework\ModuleAutoloader;
use Runa\ModuleFramework\ModuleDiscovery;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\ModuleFramework\Work\WorkDefinitions;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Development\ModuleContractSuite;
use Runa\Support\Modules\Development\ModuleGenerator;
use Runa\Support\Modules\Frontend\ModuleFrontend;

beforeEach(function (): void {
    $this->directory = sys_get_temp_dir().'/runa-generator-'.bin2hex(random_bytes(8));
    mkdir($this->directory.'/src/runa', 0755, true);
    mkdir($this->directory.'/.git', 0755, true);
    mkdir($this->directory.'/tools/maintainer', 0755, true);
    mkdir($this->directory.'/docs/runa/maintainer', 0755, true);
    file_put_contents($this->directory.'/docs/runa/maintainer/agent-contract.md', 'Owner fixture');
    file_put_contents($this->directory.'/VERSION', '0.3.1');
    copy(base_path('runa/platform.json'), $this->directory.'/src/runa/platform.json');
    $this->generator = new ModuleGenerator($this->directory, true);
    $this->code = 'generated'.bin2hex(random_bytes(6));
    $this->id = 'app.'.$this->code;
    $this->relative = 'src/app/modules/'.$this->code;
});

afterEach(function (): void {
    DB::table('shared.modules')->where('module_id', $this->id)->delete();
    DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    File::deleteDirectory($this->directory);
    $lock = sys_get_temp_dir().'/runa-generator-'.hash('sha256', $this->directory).'.lock';
    if (is_file($lock)) {
        unlink($lock);
    }
});

it('previews without writes and creates both owner skeletons with central docs and disabled registry defaults', function (): void {
    foreach (['app', 'runa'] as $owner) {
        $id = $owner.'.'.$this->code;
        $plan = $this->generator->plan($id);
        expect(is_dir($this->directory.'/src/'.$owner.'/modules'))->toBeFalse();
        $this->generator->write($plan);
        $source = (new ModuleDiscovery($this->directory.'/src', true))->discover($owner)[0];
        expect($source->manifest->id)->toBe($id)->and($source->manifest->kind)->toBe('optional')
            ->and($source->manifest->version)->toBe('0.1.0')
            ->and((new ModuleContractSuite($this->directory.'/src', true))->source($id))->toBe([])
            ->and($source->manifest->definition['docs'])->toBe([($owner === 'app' ? 'docs/app/modules/' : 'docs/runa/handbook/modules/').$this->code.'/README.md']);
        $test = file_get_contents($this->directory.'/tests/backend/'.$owner.'/modules/'.$this->code.'/ModuleContractTest.php');
        expect($test)->not->toContain('{{')->and($test)->toContain('uses(\\Tests\\');
    }
    $registry = new ModuleRegistry(DB::connection(), new ModuleDiscovery($this->directory.'/src', true));
    $record = $registry->synchronize('app')->modules[$this->id];
    expect($record->enabled)->toBeFalse()->and($record->installedVersion)->toBeNull();
});

it('generates every element using owned namespace schema classified routes guarded APIs and tests', function (): void {
    $this->generator->write($this->generator->plan($this->id));
    foreach (['model', 'migration', 'service', 'action', 'component', 'test', 'controller', 'api'] as $type) {
        $plan = $this->generator->plan($this->id, $type, 'Sample'.ucfirst($type));
        $overwrites = [];
        foreach ($plan as $path => $contents) {
            expect($contents)->not->toContain('{{');
            if (is_file($this->directory.'/'.$path)) {
                $overwrites[$path] = hash_file('sha256', $this->directory.'/'.$path);
            }
        }
        $this->generator->write($plan, $overwrites);
    }
    expect((new ModuleContractSuite($this->directory.'/src', true))->source($this->id))->toBe([]);
    $source = (new ModuleDiscovery($this->directory.'/src', true))->discover('app')[0];
    $namespace = $source->manifest->prefix;
    $model = file_get_contents($this->directory.'/'.$this->relative.'/backend/Models/SampleModel.php');
    expect($model)->toContain('app_'.$this->code.'.sample_models')->toContain('ModuleModel');
    $registry = new ModuleRegistry(DB::connection(), new ModuleDiscovery($this->directory.'/src', true));
    $record = $registry->synchronize('app')->modules[$this->id];
    $loader = new ModuleAutoloader($this->directory.'/src', $registry, true);
    app()->instance(ModuleRegistry::class, $registry);
    app()->instance(ModuleAutoloader::class, $loader);
    app()->instance(ModuleDeclarations::class, new ModuleDeclarations($this->directory.'/src', app(), $loader, true));
    app()->instance(LifecyclePolicies::class, new LifecyclePolicies($this->directory.'/src', app(), $loader, true));
    app()->instance(HealthPolicies::class, new HealthPolicies($this->directory.'/src', app(), $loader, true));
    foreach ([ModuleApis::class, ModuleHttp::class, WorkDefinitions::class, ModuleConsole::class, ModuleFrontend::class, EventDefinitions::class] as $service) {
        app()->forgetInstance($service);
    }
    $suite = new ModuleContractSuite($this->directory.'/src', true);
    expect($suite->runtime($record))->toBe([])->and($registry->snapshot()->modules[$this->id]->enabled)->toBeFalse();
    $endpoints = app(ModuleHttp::class)->endpoints($record);
    expect($endpoints)->toHaveCount(1)->and($endpoints[0]->operation->key)->toBe('sample_controller.read');
    $api = app(ModuleApis::class)->resolve($this->id, $namespace.'Contracts\\SampleApi', '1.0.0');
    expect(fn () => $api->read())->toThrow(OperationRejected::class);
    file_put_contents($this->directory.'/'.$this->relative.'/module.json', "\n", FILE_APPEND);
    expect($suite->runtime($record))->toBe(['runtime:invalid_or_stale_descriptor']);
});

it('rejects traversal invalid identifiers reserved skeletons collisions and symlink ancestors with no partial files', function (): void {
    foreach (['app../escape', 'app.bad-code', 'other.test', 'app.shared', 'app.auth/escape'] as $id) {
        expect(function () use ($id): void {
            $this->generator->write($this->generator->plan($id));
        })->toThrow(InvalidArgumentException::class);
    }
    expect(is_dir($this->directory.'/src/app/modules'))->toBeFalse();
    $this->generator->write($this->generator->plan($this->id));
    expect(fn () => $this->generator->plan($this->id))->toThrow(InvalidArgumentException::class);
    foreach (['../Bad', 'Bad/Name', 'Bad\\Name', 'Name.php', 'lowercase', 'Class', 'String', 'Enum'] as $name) {
        expect(fn () => $this->generator->plan($this->id, 'service', $name))->toThrow(InvalidArgumentException::class);
    }
    symlink($this->directory.'/src/runa', $this->directory.'/'.$this->relative.'/backend/Services');
    expect(fn () => $this->generator->plan($this->id, 'service', 'Safe'))->toThrow(InvalidArgumentException::class);
    expect(is_file($this->directory.'/src/runa/Safe.php'))->toBeFalse();
});

it('refuses production and customer Runa generation even with copied owner files', function (): void {
    expect(fn () => (new ModuleGenerator($this->directory, false))->plan($this->id))->toThrow(InvalidArgumentException::class);
    file_put_contents($this->directory.'/.git/runa-bootstrap.json', '{"schema_version":1,"mode":"dev"}');
    expect(fn () => $this->generator->plan('runa.'.$this->code))->toThrow(InvalidArgumentException::class);
    $this->generator->write($this->generator->plan($this->id));
    expect(is_file($this->directory.'/'.$this->relative.'/module.json'))->toBeTrue();
    file_put_contents($this->directory.'/.git/runa-bootstrap.json', '{"schema_version":1,"mode":"prod"}');
    expect(fn () => $this->generator->plan('app.another'))->toThrow(InvalidArgumentException::class);
});

it('requires exact scoped overwrite hashes and preflights every target before mutation', function (): void {
    $this->generator->write($this->generator->plan($this->id));
    $plan = $this->generator->plan($this->id, 'controller', 'ReadController');
    $manifest = $this->relative.'/module.json';
    $before = file_get_contents($this->directory.'/'.$manifest);
    expect(fn () => $this->generator->write($plan))->toThrow(RuntimeException::class);
    expect(is_file($this->directory.'/'.$this->relative.'/backend/Http/ReadController.php'))->toBeFalse();
    expect(fn () => $this->generator->write($plan, [$manifest => str_repeat('0', 64)]))->toThrow(RuntimeException::class);
    expect(fn () => $this->generator->write($plan, ['src/app/config/foreign.php' => hash('sha256', '')]))->toThrow(InvalidArgumentException::class);
    $this->generator->write($plan, [$manifest => hash('sha256', $before)]);
    $plan = $this->generator->plan($this->id, 'controller', 'ReadController');
    expect(fn () => $this->generator->write($plan))->toThrow(RuntimeException::class);
    $overwrites = [];
    foreach ($plan as $path => $contents) {
        $overwrites[$path] = hash_file('sha256', $this->directory.'/'.$path);
    }
    $this->generator->write($plan, $overwrites);
});

it('detects literal foreign boundaries unclassified entrypoints and ignores comments and public contracts', function (): void {
    $suite = new ModuleContractSuite($this->directory.'/src', true);
    expect($suite->lint('<?php use App\\Modules\\Foreign\\Models\\Invoice; DB::table("app_foreign.invoices");', 'App\\Modules\\Own\\', 'app_own'))->toBe(['foreign_module_import', 'foreign_schema_reference'])
        ->and($suite->lint('<?php use App\\Modules\\Foreign\\Contracts\\Invoice; // DB::table("app_foreign.rows");', 'App\\Modules\\Own\\', 'app_own'))->toBe([])
        ->and($suite->lint('<?php Route::get("foo", Handler::class);', 'App\\Modules\\Own\\', 'app_own'))->toContain('unclassified_entrypoint')
        ->and($suite->lint('<?php final class Foo extends Command {}', 'App\\Modules\\Own\\', 'app_own'))->toContain('unclassified_entrypoint')
        ->and($suite->lint('<?php $path = "modules/app/other/private";', 'App\\Modules\\Own\\', 'app_own'))->toContain('direct_module_path');
});

it('exposes safe Artisan preview and static check without changing registry generations', function (): void {
    $before = DB::table('shared.module_registry_state')->value('generation');
    expect(Artisan::call('runa:make:module', ['module' => $this->id]))->toBe(0);
    expect(Artisan::output())->toContain('candidate_sha256')->toContain('CHANGELOG.md');
    expect(is_dir(base_path('app/modules/'.$this->code)))->toBeFalse();
    expect(Artisan::call('runa:modules:check', ['owner' => 'app']))->toBe(0);
    expect(DB::table('shared.module_registry_state')->value('generation'))->toBe($before);
});

it('reports required dependency blockers and foreign source imports without registry publication', function (): void {
    $this->generator->write($this->generator->plan($this->id));
    $path = $this->directory.'/'.$this->relative.'/module.json';
    $manifest = json_decode(file_get_contents($path));
    $manifest->dependencies->required = (object) ['app.absent' => '^1.0.0'];
    file_put_contents($path, json_encode($manifest));
    $errors = (new ModuleContractSuite($this->directory.'/src', true))->source($this->id);
    expect(implode(' ', $errors))->toContain('not_registered')->toContain('app.absent');
    $manifest->dependencies->required = (object) [];
    file_put_contents($path, json_encode($manifest));
    file_put_contents($this->directory.'/'.$this->relative.'/backend/Foreign.php', '<?php use App\\Modules\\Foreign\\Models\\Record;');
    expect((new ModuleContractSuite($this->directory.'/src', true))->source($this->id))->toBe(['backend/Foreign.php:foreign_module_import']);
});

it('restores complete source and docs when final discovery finds a namespace collision', function (): void {
    $ordinary = $this->directory.'/src/app/Modules/'.ucfirst($this->code);
    mkdir($ordinary, 0755, true);
    file_put_contents($ordinary.'/Existing.php', '<?php // preserved ordinary source');
    $plan = $this->generator->plan($this->id);
    expect(fn () => $this->generator->write($plan))->toThrow(InvalidArgumentException::class);
    expect(is_dir($this->directory.'/'.$this->relative))->toBeFalse()
        ->and(is_dir($this->directory.'/docs/app/modules/'.$this->code))->toBeFalse()
        ->and(file_get_contents($ordinary.'/Existing.php'))->toBe('<?php // preserved ordinary source');
});
