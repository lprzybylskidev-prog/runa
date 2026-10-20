<?php

use Illuminate\Support\Facades\Artisan;
use Runa\Providers\RunaServiceProvider;
use Runa\Support\ApplicationManifest;
use Runa\Support\VersionCompatibility;

it('matches the documented stable compatibility grammar', function (string $range, string $version, bool $expected) {
    expect((new VersionCompatibility)->supports($range, $version))->toBe($expected);
})->with([
    ['1.2.3', '1.2.3', true], ['=1.2.3', '1.2.4', false],
    ['>=0.2.1 <0.4.0', '0.3.0', true], ['>=0.2.1 <0.4.0', '0.4.0', false],
    ['>1.0.0 <=2.0.0', '1.0.0', false], ['>1.0.0 <=2.0.0', '2.0.0', true],
    ['^1.2.3', '1.9.0', true], ['^1.2.3', '2.0.0', false],
    ['^0.2.3', '0.3.0', false], ['^0.0.3', '0.0.4', false],
    ['~1.2.3', '1.2.9', true], ['~1.2.3', '1.3.0', false],
    ['^0.2.1 || ^0.3.0', '0.3.2', true], ['^0.2.1 || ^0.3.0', '0.4.0', false],
]);

it('rejects unsupported syntax even in an alternative after a successful match', function (string $range) {
    expect(fn () => (new VersionCompatibility)->supports($range, '1.2.3'))->toThrow(InvalidArgumentException::class);
})->with(['', '*', '1.2', '1.*', 'v1.2.3', '>= 1.2.3', '1.2.3 - 2.0.0', '1.2.3 || *', '1.2.3 ||', '^01.2.3', '1.2.3-beta', '>=9999999999.0.0', "1.2.3\n*"]);

it('validates SemVer application versions without silently accepting leading zeroes', function (string $version, bool $valid) {
    expect(VersionCompatibility::validVersion($version))->toBe($valid);
})->with([['1.2.3', true], ['1.2.3-beta.1+build.2', true], ['0.0.0+001', true],
    ['01.2.3', false], ['1.2.3-01', false], ['1.2.3+', false], ['1.2', false]]);

function manifestFixture(array $overrides = []): string
{
    $path = tempnam(sys_get_temp_dir(), 'runa-manifest-');
    file_put_contents($path, json_encode(array_replace([
        'schema_version' => 1, 'id' => 'customer-app', 'name' => 'Customer app',
        'version' => '2.4.1', 'runa' => '>=0.2.1 <0.4.0',
    ], $overrides), JSON_THROW_ON_ERROR));

    return $path;
}

it('keeps application identity/version unchanged across compatible platform updates', function () {
    $path = manifestFixture(['branding' => ['logo' => ['src' => '/brand.png', 'accessible_name' => 'Customer']]]);
    try {
        $before = file_get_contents($path);
        $manifest = ApplicationManifest::load($path);
        $manifest->assertCompatible('0.2.1');
        $manifest->assertCompatible('0.3.0');
        expect($manifest->version)->toBe('2.4.1')->and($manifest->id)->toBe('customer-app')
            ->and($manifest->logo)->toBe(['src' => '/brand.png', 'accessibleName' => 'Customer'])
            ->and(file_get_contents($path))->toBe($before);
        expect(fn () => $manifest->assertCompatible('0.4.0'))->toThrow(InvalidArgumentException::class, 'incompatible');
    } finally {
        unlink($path);
    }
});

it('rejects invalid schema, identity, range and unsafe branding metadata', function (array $overrides) {
    $path = manifestFixture($overrides);
    try {
        expect(fn () => ApplicationManifest::load($path))->toThrow(InvalidArgumentException::class);
    } finally {
        unlink($path);
    }
})->with([
    [['schema_version' => 2]], [['schema_version' => '1']], [['id' => '../escape']], [['id' => '']],
    [['name' => "Name\nInjected"]], [['version' => '01.0.0']], [['runa' => '*']], [['unknown' => true]],
    [['branding' => ['logo' => ['src' => '//outside.invalid/logo.png']]]],
    [['branding' => ['logo' => ['src' => 'https://user:pass@example.invalid/logo.png']]]],
    [['branding' => ['logo' => ['src' => 'javascript:alert(1)']]]],
    [['branding' => ['logo' => ['src' => '/\\outside.invalid/logo.png']]]],
    [['branding' => ['logo' => ['src' => '/brand.png', 'accessible_name' => 123]]]],
    [['branding' => ['secret' => 'not-public']]],
]);

it('rejects missing, malformed, oversized and linked manifests', function () {
    $path = manifestFixture();
    $link = $path.'-link';
    try {
        symlink($path, $link);
        expect(fn () => ApplicationManifest::load($link))->toThrow(InvalidArgumentException::class);
        file_put_contents($path, '{');
        expect(fn () => ApplicationManifest::load($path))->toThrow(InvalidArgumentException::class);
        file_put_contents($path, str_repeat(' ', 16385));
        clearstatcache(true, $path);
        expect(fn () => ApplicationManifest::load($path))->toThrow(InvalidArgumentException::class);
        expect(fn () => ApplicationManifest::load($path.'-missing'))->toThrow(InvalidArgumentException::class);
    } finally {
        unlink($link);
        unlink($path);
    }
});

it('serves only public identity fields and preserves independent versions', function () {
    config(['branding.application_name' => 'Customer', 'branding.application_version' => '2.4.1',
        'branding.platform_version' => '0.3.0', 'branding.mail_logo_path' => '/private/never-public.png']);
    $this->getJson('/api/identity')->assertOk()->assertHeader('Cache-Control', 'no-store, private')
        ->assertExactJson(['data' => ['applicationId' => 'runa', 'applicationName' => 'Customer',
            'applicationVersion' => '2.4.1', 'platformVersion' => '0.3.0']]);
});

it('checks candidate compatibility without modifying the application manifest', function () {
    $before = file_get_contents(app_path('application.json'));
    expect(Artisan::call('runa:application:check', ['--platform-version' => '0.3.0']))->toBe(0);
    expect(Artisan::call('runa:application:check', ['--platform-version' => '1.0.0']))->toBe(1);
    expect(Artisan::call('runa:application:check', ['--platform-version' => '1.*']))->toBe(1);
    expect(file_get_contents(app_path('application.json')))->toBe($before);
});

it('rejects canonical identity overrides in customer branding config', function () {
    $original = app()->path();
    $temporary = sys_get_temp_dir().'/runa-identity-'.bin2hex(random_bytes(6));
    mkdir($temporary.'/config', 0775, true);
    file_put_contents($temporary.'/config/branding.php', "<?php return ['application_version' => '9.9.9'];");
    try {
        app()->useAppPath($temporary);
        expect(fn () => (new RunaServiceProvider(app()))->register())
            ->toThrow(RuntimeException::class, 'app/application.json');
    } finally {
        app()->useAppPath($original);
        unlink($temporary.'/config/branding.php');
        rmdir($temporary.'/config');
        rmdir($temporary);
    }
});
