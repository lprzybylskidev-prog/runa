<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

it('registers review entry points only in local and testing environments', function (string $environment, bool $available) {
    $originalRouter = Route::getFacadeRoot();
    $originalEnvironment = $this->app->environment();
    $router = new Router($this->app['events'], $this->app);

    try {
        $this->app->instance('env', $environment);
        Route::swap($router);
        require base_path('routes/spa.php');
        $router->getRoutes()->refreshNameLookups();

        expect($router->getRoutes()->getByName('spa'))->not->toBeNull();
        expect($router->getRoutes()->getByName('review.ui') !== null)->toBe($available);
        expect($router->getRoutes()->getByName('review.email') !== null)->toBe($available);
        foreach ([403, 404, 419, 429, 500, 503, 'emergency'] as $status) {
            expect($router->getRoutes()->getByName('review.errors.'.$status) !== null)->toBe($available);
        }
        foreach (['overview', 'projects', 'activity', 'archive', 'team'] as $page) {
            expect($router->getRoutes()->getByName('review.application.'.$page) !== null)->toBe($available);
        }
    } finally {
        Route::swap($originalRouter);
        $this->app->instance('env', $originalEnvironment);
    }
})->with([
    ['local', true], ['testing', true], ['production', false], ['staging', false],
]);

it('rejects even a stale cached review route in production', function () {
    $this->app->instance('env', 'production');

    $this->get('/__review/email')->assertNotFound();
    $this->get('/__review/errors/403')->assertNotFound();
    $this->get('/__review/errors/emergency')->assertNotFound();
    $this->get('/__review/ui')->assertNotFound();
    $this->get('/__review/application/overview')->assertNotFound();
});

it('serves a separate review document without changing the root entry', function () {
    $public = storage_path('ui-review-http');
    $this->app->usePublicPath($public);
    File::ensureDirectoryExists($public.'/ui-review');
    File::ensureDirectoryExists($public.'/build');
    File::put($public.'/ui-review/index.html', '<!doctype html><title>Review fixture</title>');
    File::put($public.'/build/index.html', '<!doctype html><title>Root fixture</title>');

    try {
        $review = $this->get('/__review/ui')->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
            ->assertHeader('Cache-Control', 'no-store, private');
        expect($review->baseResponse->getFile()->getPathname())->toBe($public.'/ui-review/index.html');
        foreach (['overview', 'projects', 'activity', 'archive', 'team'] as $page) {
            $this->get('/__review/application/'.$page)->assertOk();
        }
        $this->get('/__review/application/unknown')->assertNotFound();
        $root = $this->get('/')->assertOk();
        expect($root->baseResponse->getFile()->getPathname())->toBe($public.'/build/index.html');
        $this->get('/__review/not-found')->assertNotFound();
        $this->getJson('/api/__review/ui')->assertNotFound()->assertJsonStructure(['message']);
        File::delete($public.'/ui-review/index.html');
        $this->get('/__review/ui')->assertNotFound();
    } finally {
        File::deleteDirectory($public);
    }
});
