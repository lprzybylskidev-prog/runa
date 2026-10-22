<?php

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Runa\Support\Modules\ModuleAutoloader;
use Runa\Support\Modules\ModuleDiscovery;
use Runa\Support\Modules\ModuleManifest;
use Runa\Support\Modules\ModuleRegistry;
use Tests\Fixtures\ModuleSources;

uses(DatabaseTransactions::class);

beforeEach(function (): void {
    $this->sources = new ModuleSources;
    $this->discovery = new ModuleDiscovery($this->sources->root);
    $this->registry = new ModuleRegistry(DB::connection(), $this->discovery);
});

afterEach(function (): void {
    $this->sources->remove();
});

it('discovers deterministically and preserves flags and installed data through install update missing and restore', function (): void {
    $this->sources->add('app', 'zeta');
    $this->sources->add('app', 'filesystem');
    $this->sources->add('runa', 'filesystem', 'core');
    expect(array_map(fn ($source) => $source->manifest->id, $this->discovery->discover('app')))->toBe(['app.filesystem', 'app.zeta']);
    $first = $this->registry->synchronize('app');
    $record = $first->modules['app.filesystem'];
    expect($record->enabled)->toBeFalse()->and($record->installedVersion)->toBeNull()
        ->and($record->registered)->toBeTrue()->and($record->sourcePresent)->toBeTrue()
        ->and($record->publicId[14])->toBe('7');
    expect($this->registry->synchronize('app'))->toEqual($first);
    $core = $this->registry->synchronize('runa');
    expect($core->modules['runa.filesystem']->enabled)->toBeTrue()
        ->and($core->generation)->toBe($first->generation + 1);
    DB::table('shared.modules')->where('module_id', 'app.filesystem')->update(['enabled' => true, 'installed_version' => '0.1.0']);
    // Preserve manifest object/list distinctions when editing source.
    $object = json_decode(file_get_contents($this->sources->root.'/app/modules/filesystem/module.json'));
    $object->version = '0.2.0';
    file_put_contents($this->sources->root.'/app/modules/filesystem/module.json', json_encode($object));
    $updated = $this->registry->synchronize('app');
    expect($updated->modules['app.filesystem']->enabled)->toBeTrue()
        ->and($updated->modules['app.filesystem']->installedVersion)->toBe('0.1.0')
        ->and($updated->modules['app.filesystem']->sourceVersion)->toBe('0.2.0')
        ->and($updated->modules['app.filesystem']->publicId)->toBe($record->publicId);
    rename($this->sources->root.'/app/modules/filesystem', $this->sources->root.'/retained');
    $missing = $this->registry->synchronize('app');
    expect($missing->modules['app.filesystem']->sourcePresent)->toBeFalse()
        ->and($missing->modules['app.filesystem']->enabled)->toBeTrue()
        ->and($missing->modules['app.filesystem']->installedVersion)->toBe('0.1.0');
    rename($this->sources->root.'/retained', $this->sources->root.'/app/modules/filesystem');
    expect($this->registry->synchronize('app')->modules['app.filesystem']->sourcePresent)->toBeTrue();
    File::deleteDirectory($this->sources->root.'/runa/modules/filesystem');
    expect($this->registry->synchronize('runa')->modules['runa.filesystem']->sourcePresent)->toBeFalse();
});

it('rolls back the complete publication when existing kind changes', function (): void {
    $definition = $this->sources->add('app', 'zeta');
    $before = $this->registry->synchronize('app');
    $this->sources->add('app', 'alpha');
    $definition['kind'] = 'core';
    $this->sources->write('app', 'zeta', $definition);
    expect(fn () => $this->registry->synchronize('app'))->toThrow(RuntimeException::class);
    expect($this->registry->snapshot())->toEqual($before)
        ->and(DB::table('shared.modules')->where('module_id', 'app.alpha')->exists())->toBeFalse();
});

it('invalidates cached snapshots across registry instances and never writes on reads or HTTP boot', function (): void {
    $reader = new ModuleRegistry(DB::connection(), $this->discovery);
    $before = $reader->snapshot();
    $this->sources->add('app', 'alpha');
    $published = $this->registry->synchronize('app');
    expect($reader->snapshot()->generation)->toBe($published->generation)
        ->and($reader->snapshot()->modules)->toHaveKey('app.alpha')
        ->and($before->modules)->not->toHaveKey('app.alpha');
    DB::connection()->enableQueryLog();
    $reader->snapshot();
    $this->getJson('/api/health')->assertSuccessful();
    foreach (DB::getQueryLog() as $query) {
        expect($query['query'])->not->toMatch('/\b(insert|update|delete|create|alter)\b/i');
    }
    DB::connection()->disableQueryLog();
});

it('serializes competing synchronization using a real independent PostgreSQL connection', function (): void {
    $this->sources->add('app', 'alpha');
    $first = $this->registry->synchronize('app'); // Outer test transaction retains the publication lock.
    config()->set('database.connections.module_competitor', config('database.connections.pgsql'));
    $competitor = DB::connection('module_competitor');
    try {
        $competitor->statement("SET lock_timeout = '100ms'");
        $registry = new ModuleRegistry($competitor, $this->discovery);
        expect(fn () => $registry->synchronize('app'))->toThrow(QueryException::class);
        expect($this->registry->snapshot())->toEqual($first);
    } finally {
        DB::purge('module_competitor');
    }
});

it('enforces bigint identity uniqueness owner path version and mandatory Core in actual DDL', function (): void {
    $this->sources->add('runa', 'alpha', 'core');
    $this->registry->synchronize('runa');
    $identity = DB::selectOne("SELECT data_type, is_identity, identity_generation FROM information_schema.columns WHERE table_schema = 'shared' AND table_name = 'modules' AND column_name = 'id'");
    expect($identity->data_type)->toBe('bigint')->and($identity->is_identity)->toBe('YES')->and($identity->identity_generation)->toBe('ALWAYS');
    foreach ([['enabled' => false], ['owner' => 'app'], ['source_path' => '../escape'], ['source_version' => '01.0.0'], ['kind' => 'unknown'], ['manifest' => '[]']] as $values) {
        expect(fn () => DB::transaction(fn () => DB::table('shared.modules')->where('module_id', 'runa.alpha')->update($values)))->toThrow(QueryException::class);
    }
    $row = (array) DB::table('shared.modules')->where('module_id', 'runa.alpha')->first();
    unset($row['id']);
    expect(fn () => DB::transaction(fn () => DB::table('shared.modules')->insert($row)))->toThrow(QueryException::class);
});

it('loads only synchronized owned module classes without per-module Composer changes', function (): void {
    $this->sources->add('app', 'loader_fixture');
    $this->registry->synchronize('app');
    $loader = new ModuleAutoloader($this->sources->root, $this->registry, false);
    $loader->load('App\\Modules\\LoaderFixture\\Definitions\\WorkPolicy');
    expect(class_exists('App\\Modules\\LoaderFixture\\Definitions\\WorkPolicy', false))->toBeTrue();
    $this->sources->add('app', 'stale_fixture');
    $this->registry->synchronize('app');
    file_put_contents($this->sources->root.'/app/modules/stale_fixture/module.json', "\n", FILE_APPEND);
    expect(fn () => $loader->load('App\\Modules\\StaleFixture\\Definitions\\WorkPolicy'))->toThrow(RuntimeException::class);
});

it('rejects namespace collisions and every symlink boundary before source execution', function (): void {
    $this->sources->add('app', 'file_system');
    $this->sources->add('app', 'filesystem');
    expect(fn () => $this->discovery->discover('app'))->toThrow(InvalidArgumentException::class);
    File::deleteDirectory($this->sources->root.'/app/modules/file_system');
    mkdir($this->sources->root.'/app/Modules/Filesystem', 0775, true);
    expect(fn () => $this->discovery->discover('app'))->toThrow(InvalidArgumentException::class);
    File::deleteDirectory($this->sources->root.'/app/Modules');
    symlink($this->sources->root.'/app/modules/filesystem/docs', $this->sources->root.'/app/modules/filesystem/backend/escape');
    expect(fn () => $this->discovery->discover('app'))->toThrow(InvalidArgumentException::class);
    unlink($this->sources->root.'/app/modules/filesystem/backend/escape');
    rename($this->sources->root.'/app/modules/filesystem/module.json', $this->sources->root.'/manifest.json');
    symlink($this->sources->root.'/manifest.json', $this->sources->root.'/app/modules/filesystem/module.json');
    expect(fn () => $this->discovery->discover('app'))->toThrow(InvalidArgumentException::class);
});

it('accepts an empty module-free foundation and rejects the probe outside development', function (): void {
    expect($this->discovery->discover('runa'))->toBe([]);
    $this->sources->add('app', 'module_probe');
    expect(fn () => $this->discovery->discover('app'))->toThrow(InvalidArgumentException::class);
    $development = new ModuleDiscovery($this->sources->root, true);
    expect($development->discover('app')[0]->manifest->id)->toBe('app.module_probe');
    $probe = new ModuleDiscovery(base_path(), true);
    expect(array_column(array_map(fn ($source) => ['id' => $source->manifest->id], $probe->discover('app')), 'id'))->toContain('app.module_probe');
    expect(fn () => (new ModuleDiscovery(base_path()))->discover('app'))->toThrow(InvalidArgumentException::class);
    expect(file_get_contents(dirname(base_path()).'/.dockerignore'))->toContain('src/app/modules/module_probe');
});

it('rejects invalid and ambiguous manifests', function (Closure $mutate): void {
    $definition = $this->sources->add('app', 'alpha');
    $mutate($definition);
    $this->sources->write('app', 'alpha', $definition);
    expect(fn () => $this->discovery->discover('app'))->toThrow(InvalidArgumentException::class);
})->with([
    'unknown field' => [fn (&$d) => $d['unknown'] = true],
    'missing field' => [function (&$d): void {
        unset($d['docs']);
    }],
    'unknown revision' => [fn (&$d) => $d['schema_version'] = 2],
    'wrong identity' => [fn (&$d) => $d['id'] = 'runa.alpha'],
    'invalid kind' => [fn (&$d) => $d['kind'] = 'plugin'],
    'unstable version' => [fn (&$d) => $d['version'] = '1.0.0-beta'],
    'wildcard constraint' => [fn (&$d) => $d['platform'] = '*'],
    'oversized version' => [fn (&$d) => $d['version'] = '1000000000.0.0'],
    'empty localization' => [fn (&$d) => $d['name']->en = ' '],
    'array dependency map' => [fn (&$d) => $d['dependencies']->required = []],
    'self dependency' => [fn (&$d) => $d['dependencies']->required->{'app.alpha'} = '^1.0.0'],
    'overlapping dependencies' => [function (&$d): void {
        $d['dependencies']->required->{'app.beta'} = '^1.0.0';
        $d['dependencies']->optional->{'app.beta'} = '^1.0.0';
    }],
    'foreign provider' => [fn (&$d) => $d['providers'] = ['App\\Other\\Provider']],
    'missing class' => [fn (&$d) => $d['providers'] = ['App\\Modules\\Alpha\\Missing']],
    'missing policy' => [fn (&$d) => $d['declarations']->lifecycle = []],
    'unknown declaration' => [fn (&$d) => $d['declarations']->unknown = []],
    'doc traversal' => [fn (&$d) => $d['docs'] = ['docs/../../alpha/module.json.md']],
]);

it('rejects owner direction reserved codes and duplicate full identities without publishing', function (): void {
    $definition = $this->sources->add('runa', 'alpha');
    $definition['dependencies']->optional->{'app.beta'} = '^1.0.0';
    $this->sources->write('runa', 'alpha', $definition);
    expect(fn () => $this->registry->synchronize('runa'))->toThrow(InvalidArgumentException::class);
    $definition = $this->sources->add('app', 'beta');
    $this->sources->add('app', 'alpha');
    $definition['id'] = 'app.alpha';
    $this->sources->write('app', 'beta', $definition);
    expect(fn () => $this->registry->synchronize('app'))->toThrow(InvalidArgumentException::class);
    expect(ModuleManifest::validCode(str_repeat('a', 59)))->toBeFalse();
    $this->sources->add('app', 'shared');
    expect(fn () => ModuleManifest::load($this->sources->root.'/app/modules/shared/module.json', 'app', 'shared'))->toThrow(InvalidArgumentException::class);
});

it('keeps registry reads and command argument validation independent of discovery writes', function (): void {
    $before = $this->registry->snapshot();
    expect(Artisan::call('runa:modules:sync', ['owner' => 'all']))->toBe(1);
    expect($this->registry->snapshot())->toEqual($before);
    $this->app->instance(ModuleRegistry::class, $this->registry);
    $this->sources->add('app', 'alpha');
    expect(Artisan::call('runa:modules:sync', ['owner' => 'app']))->toBe(0);
    expect($this->registry->snapshot()->modules['app.alpha']->enabled)->toBeFalse();
});

it('rejects escaped duplicate JSON keys and oversized manifests', function (): void {
    $this->sources->add('app', 'alpha');
    $file = $this->sources->root.'/app/modules/alpha/module.json';
    $json = file_get_contents($file);
    file_put_contents($file, str_replace('"schema_version":1', '"schema_version":1,"schema_versi\\u006fn":1', $json));
    expect(fn () => ModuleManifest::load($file, 'app', 'alpha'))->toThrow(InvalidArgumentException::class);
    file_put_contents($file, $json.str_repeat(' ', 65536));
    clearstatcache();
    expect(fn () => ModuleManifest::load($file, 'app', 'alpha'))->toThrow(InvalidArgumentException::class);
});

it('rejects symlinked owners even with no module directory and rejects declaration directories', function (): void {
    mkdir($this->sources->root.'/other');
    symlink($this->sources->root.'/other', $this->sources->root.'/app');
    expect(fn () => $this->discovery->discover('app'))->toThrow(InvalidArgumentException::class);
    unlink($this->sources->root.'/app');
    $this->sources->add('app', 'alpha');
    $file = $this->sources->root.'/app/modules/alpha/backend/Definitions/WorkPolicy.php';
    unlink($file);
    mkdir($file);
    expect(fn () => $this->discovery->discover('app'))->toThrow(InvalidArgumentException::class);
});
