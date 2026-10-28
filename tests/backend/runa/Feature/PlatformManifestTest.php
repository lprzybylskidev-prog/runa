<?php

use Runa\Identity\ApplicationManifest;
use Runa\Identity\PlatformManifest;

it('keeps the placeholder application and installed platform versions independent', function () {
    $platform = PlatformManifest::load(base_path('runa/platform.json'));
    $application = ApplicationManifest::load(app_path('application.json'));
    expect($application->name)->toBe('Runa')->and($application->version)->toBe('0.0.0')
        ->and($platform->id)->toBe('runa')->and($platform->version)->not->toBe($application->version);
    $this->getJson('/api/identity')->assertOk()->assertJsonPath('data.applicationVersion', '0.0.0')
        ->assertJsonPath('data.platformVersion', $platform->version);
});

it('rejects invalid schema and malformed or missing files', function (string $content) {
    $path = tempnam(sys_get_temp_dir(), 'runa-platform-');
    file_put_contents($path, $content);
    try {
        expect(fn () => PlatformManifest::load($path))->toThrow(InvalidArgumentException::class);
    } finally {
        unlink($path);
    }
})->with([
    '{', '[]', '{"schema_version":2,"id":"runa","name":"Runa","version":"1.2.3"}',
    '{"schema_version":1,"id":"other","name":"Runa","version":"1.2.3"}',
    '{"schema_version":1,"id":"runa","name":"Runa","version":"01.2.3"}',
    '{"schema_version":1,"id":"runa","name":"Runa","version":"1.2.3","unknown":true}',
]);

it('accepts platform identity independently of customer scaffold without changing the application manifest', function () {
    $path = tempnam(sys_get_temp_dir(), 'runa-platform-');
    $application = file_get_contents(app_path('application.json'));
    try {
        file_put_contents($path, '{"schema_version":1,"id":"runa","name":"Runa","version":"1.2.3"}');
        expect(PlatformManifest::load($path)->version)->toBe('1.2.3');
        file_put_contents($path, '{"schema_version":1,"id":"runa","name":"Runa","version":"1.2.4"}');
        expect(PlatformManifest::load($path)->version)->toBe('1.2.4');
        expect(file_get_contents(app_path('application.json')))->toBe($application);
    } finally {
        unlink($path);
    }
});

it('rejects linked, missing and oversized platform inputs', function () {
    $path = tempnam(sys_get_temp_dir(), 'runa-platform-');
    $link = $path.'-link';
    try {
        symlink($path, $link);
        expect(fn () => PlatformManifest::load($link))->toThrow(InvalidArgumentException::class);
        expect(fn () => PlatformManifest::load($path.'-missing'))->toThrow(InvalidArgumentException::class);
        file_put_contents($path, str_repeat(' ', 16385));
        clearstatcache(true, $path);
        expect(fn () => PlatformManifest::load($path))->toThrow(InvalidArgumentException::class);
    } finally {
        unlink($link);
        unlink($path);
    }
});
