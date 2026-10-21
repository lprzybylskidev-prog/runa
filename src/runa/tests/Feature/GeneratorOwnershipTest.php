<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

it('generates customer artifacts under the application owner roots', function (): void {
    $name = 'GeneratorProbe'.bin2hex(random_bytes(6));
    $migration = 'create_'.strtolower($name).'_table';
    $paths = [app_path('Http/Controllers/'.$name), app_path('database/seeders/'.$name), app_path('resources/views/'.$name)];
    try {
        expect(Artisan::call('make:controller', ['name' => $name.'/SampleController']))->toBe(0);
        expect(Artisan::call('make:seeder', ['name' => $name.'/SampleSeeder']))->toBe(0);
        expect(Artisan::call('make:view', ['name' => $name.'.sample']))->toBe(0);
        expect(Artisan::call('make:migration', ['name' => $migration]))->toBe(0);
        expect(is_file($paths[0].'/SampleController.php'))->toBeTrue()
            ->and(is_file($paths[1].'/SampleSeeder.php'))->toBeTrue()
            ->and(is_file($paths[2].'/sample.blade.php'))->toBeTrue()
            ->and(glob(app_path('database/migrations/*_'.$migration.'.php')))->toHaveCount(1)
            ->and(resource_path())->toBe(app_path('resources'));
    } finally {
        foreach ($paths as $path) {
            File::deleteDirectory($path);
        }
        foreach (glob(app_path('database/migrations/*_'.$migration.'.php')) ?: [] as $path) {
            unlink($path);
        }
    }
});
