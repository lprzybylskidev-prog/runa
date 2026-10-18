<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

it('renders safe localized native error pages while preserving status and headers', function (int $status, string $locale) {
    config(['app.debug' => false]);
    $response = $this->get('/__review/errors/'.$status.'?lang='.$locale, ['X-Request-ID' => 'error-request-123']);
    $response->assertStatus($status)->assertSee('HTTP '.$status, false)
        ->assertSee('lang="'.$locale.'"', false)->assertSee('runa', false)
        ->assertDontSee('Private review exception details.')->assertDontSee('Stack trace')
        ->assertHeader('X-Request-ID', 'error-request-123');
    if (in_array($status, [429, 503], true)) {
        $response->assertHeader('Retry-After', '120');
    }
})->with([403, 404, 419, 429, 500, 503])->with(['pl', 'en']);

it('keeps JSON exception contracts on API paths and negotiated requests', function (int $status) {
    config(['app.debug' => false]);
    Route::get('/api/error-fixture', fn () => abort($status, 'Safe API message', ['Retry-After' => '17']))->name('test.api.error');
    $this->get('/api/error-fixture')->assertStatus($status)->assertJsonStructure(['message'])
        ->assertHeader('Retry-After', '17')->assertDontSee('<html', false);
    $this->getJson('/__review/errors/'.$status)->assertStatus($status)->assertJsonStructure(['message'])
        ->assertDontSee('<html', false);
})->with([403, 404, 419, 429, 500, 503]);

it('renders a real missing path without build assets or a database', function () {
    config(['app.debug' => false, 'database.connections.pgsql.host' => '127.0.0.1', 'database.connections.pgsql.port' => 1]);
    DB::purge('pgsql');
    $this->app->usePublicPath(storage_path('absent-public-error-test'));
    $this->get('/not-a-real-path', ['Accept-Language' => 'en'])->assertNotFound()
        ->assertSee('Page not found')->assertSee('/runa-error/styles.css', false)->assertDontSee('<script', false);
});

it('hides database exception details and preserves an independent liveness contract', function () {
    config(['app.debug' => false, 'database.connections.pgsql.host' => '127.0.0.1', 'database.connections.pgsql.port' => 1]);
    DB::purge('pgsql');
    Route::get('/error-db-fixture', fn () => DB::selectOne('select 1'))->name('test.error.database');
    $this->get('/error-db-fixture?lang=en')->assertStatus(500)->assertSee('Server error')
        ->assertDontSee('SQLSTATE')->assertDontSee('127.0.0.1')->assertDontSee('select 1')->assertDontSee('PDOException');
    $this->get('/api/health')->assertOk()->assertExactJson(['status' => 'ok']);
});

it('escapes configured identity and ignores unknown locale and theme values', function () {
    config(['branding.application_name' => '<script>alert(1)</script>']);
    $this->get('/missing?lang=invalid&theme=invalid', ['Accept-Language' => 'en'])
        ->assertNotFound()->assertSee('lang="en"', false)->assertSee('data-theme="system"', false)
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>', false);
});

it('serves a complete standalone emergency preview with status 503', function () {
    $public = storage_path('error-preview-fixture');
    $this->app->usePublicPath($public);
    File::ensureDirectoryExists($public.'/runa-error/pages');
    file_put_contents($public.'/runa-error/pages/503-pl-system.html', '<!doctype html><h1>Standalone emergency fixture</h1>');
    try {
        $this->get('/__review/errors/emergency')->assertStatus(503)
            ->assertSee('Standalone emergency fixture')->assertHeader('Retry-After', '120')
            ->assertHeader('Cache-Control', 'no-store, private');
    } finally {
        File::deleteDirectory($public);
    }
});
