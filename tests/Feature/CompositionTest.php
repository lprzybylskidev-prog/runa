<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Runa\Console\Commands\Database\GuardedFreshCommand;
use Runa\Providers\RunaServiceProvider;

it('composes customer code and platform behavior under one Laravel base', function (): void {
    expect(base_path())->toBe(dirname(__DIR__, 2).'/src')
        ->and(app()->path())->toBe(base_path('app'))
        ->and(config_path())->toBe(base_path('runa/config'))
        ->and(database_path())->toBe(base_path('app/database'))
        ->and(app()->environmentPath())->toBe(dirname(__DIR__, 2))
        ->and(app()->getNamespace())->toBe('App\\')
        ->and(Artisan::all()['migrate:fresh'])->toBeInstanceOf(GuardedFreshCommand::class);
    expect(app('router')->getRoutes()->getByName('api.health')->getActionName())->toBe('Runa\\Http\\Controllers\\HealthController');
    expect(app('router')->getRoutes()->getByName('spa')->getActionName())->toBe('App\\Http\\Controllers\\SpaController');
});

it('keeps identical application and platform file and cache keys isolated', function (): void {
    $application = Storage::disk('application_private');
    $platform = Storage::disk('runa_private');
    try {
        $application->put('ownership.txt', 'application');
        $platform->put('ownership.txt', 'platform');
        expect($application->get('ownership.txt'))->toBe('application')
            ->and($platform->get('ownership.txt'))->toBe('platform');
        Cache::store('file')->put('ownership', 'application', 60);
        Cache::store('runa_file')->put('ownership', 'platform', 60);
        expect(Cache::store('file')->get('ownership'))->toBe('application')
            ->and(Cache::store('runa_file')->get('ownership'))->toBe('platform');
    } finally {
        $application->delete('ownership.txt');
        $platform->delete('ownership.txt');
        Cache::store('file')->forget('ownership');
        Cache::store('runa_file')->forget('ownership');
    }
});

it('loads customer config without editing or dropping platform defaults', function (): void {
    $original = app()->path();
    $temporary = storage_path('app/application/private/override-fixture');
    mkdir($temporary.'/config', 0775, true);
    file_put_contents($temporary.'/config/cache.php', "<?php return ['prefix' => 'customer_override_'];");
    try {
        app()->useAppPath($temporary);
        (new RunaServiceProvider(app()))->register();
        expect(config('cache.prefix'))->toBe('customer_override_')
            ->and(config('cache.stores.runa_file.driver'))->toBe('file');
        file_put_contents($temporary.'/config/cache.php', "<?php return 'invalid';");
        expect(fn () => (new RunaServiceProvider(app()))->register())
            ->toThrow(RuntimeException::class, 'Application configuration must return an array.');
    } finally {
        app()->useAppPath($original);
        unlink($temporary.'/config/cache.php');
        rmdir($temporary.'/config');
        rmdir($temporary);
    }
});
