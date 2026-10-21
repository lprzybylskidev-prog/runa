<?php

// Invoked only by the loopback Playwright harness, never by the application router.
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Tests\Fixtures\BrowserMailDocument;

require __DIR__.'/../bootstrap.php';
$app = require __DIR__.'/../../../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();
config(['app.debug' => false, 'session.driver' => 'array', 'cache.default' => 'array']);
View::addNamespace('browser-mail', __DIR__.'/browser-mail');
Route::get('/__fixtures/email', BrowserMailDocument::class)->name('test.browser.email');
Route::get('/__fixtures/errors/emergency', function (Request $request) {
    $locale = $request->query('lang') === 'en' ? 'en' : 'pl';
    $theme = in_array($request->query('theme'), ['light', 'dark'], true) ? $request->query('theme') : 'system';

    return response(file_get_contents(public_path('runa-error/pages/503-'.$locale.'-'.$theme.'.html')), 503,
        ['Content-Type' => 'text/html; charset=UTF-8', 'Retry-After' => '120']);
})->name('test.browser.emergency');
Route::get('/__fixtures/errors/{status}', function (int $status) {
    abort_unless(in_array($status, [403, 404, 419, 429, 500, 503], true), 404);
    abort($status, 'Private fixture exception details.', in_array($status, [429, 503], true) ? ['Retry-After' => '120'] : []);
})->name('test.browser.errors');
$request = Request::create($argv[1], 'GET', [], [], [], ['HTTP_ACCEPT' => $argv[2] ?? 'text/html']);
$response = $kernel->handle($request);
echo json_encode(['status' => $response->getStatusCode(), 'headers' => $response->headers->all(),
    'body' => base64_encode($response->getContent())], JSON_THROW_ON_ERROR);
$kernel->terminate($request, $response);
