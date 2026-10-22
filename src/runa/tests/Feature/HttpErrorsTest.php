<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('/test-errors/{status}', function (int $status) {
        abort($status, 'Private fixture exception details.', in_array($status, [429, 503], true) ? ['Retry-After' => '120'] : []);
    })->name('test.errors.status');
});

it('renders safe localized native error pages while preserving status and headers', function (int $status, string $locale) {
    config(['app.debug' => false]);
    $response = $this->get('/test-errors/'.$status.'?lang='.$locale, ['X-Request-ID' => 'error-request-123']);
    $response->assertStatus($status)->assertSee('HTTP '.$status, false)
        ->assertSee('lang="'.$locale.'"', false)->assertSee('runa', false)
        ->assertDontSee('Private fixture exception details.')->assertDontSee('Stack trace')
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
    $this->getJson('/test-errors/'.$status)->assertStatus($status)->assertJsonStructure(['message'])
        ->assertDontSee('<html', false);
})->with([403, 404, 419, 429, 500, 503]);

it('renders runtime maintenance safely without build assets or a database', function () {
    config(['app.debug' => false, 'database.connections.pgsql.host' => '127.0.0.1', 'database.connections.pgsql.port' => 1]);
    DB::purge('pgsql');
    $this->app->usePublicPath(storage_path('absent-public-error-test'));
    $this->get('/not-a-real-path', ['Accept-Language' => 'en'])->assertStatus(503)
        ->assertSee('HTTP 503')->assertSee('/runa-error/styles.css', false)->assertDontSee('<script', false);
});

it('hides database exception details and preserves an independent liveness contract', function () {
    config(['app.debug' => false, 'database.connections.pgsql.host' => '127.0.0.1', 'database.connections.pgsql.port' => 1]);
    DB::purge('pgsql');
    Route::get('/error-db-fixture', fn () => DB::selectOne('select 1'))->name('test.error.database');
    $this->get('/error-db-fixture?lang=en')->assertStatus(503)->assertSee('HTTP 503')
        ->assertDontSee('SQLSTATE')->assertDontSee('127.0.0.1')->assertDontSee('select 1')->assertDontSee('PDOException');
    $this->get('/api/health')->assertOk()->assertExactJson(['status' => 'ok']);
});

it('escapes configured identity and ignores unknown locale and theme values', function () {
    config(['branding.application_name' => '<script>alert(1)</script>']);
    $this->get('/missing?lang=invalid&theme=invalid', ['Accept-Language' => 'en'])
        ->assertNotFound()->assertSee('lang="en"', false)->assertSee('data-theme="system"', false)
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>', false);
});
