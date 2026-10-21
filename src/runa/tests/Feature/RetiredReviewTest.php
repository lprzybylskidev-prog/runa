<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

it('has no preview or fixture routes in any application environment', function (string $environment) {
    $originalRouter = Route::getFacadeRoot();
    $originalEnvironment = $this->app->environment();
    $router = new Router($this->app['events'], $this->app);
    try {
        $this->app->instance('env', $environment);
        Route::swap($router);
        require base_path('app/routes/web.php');
        $router->getRoutes()->refreshNameLookups();
        expect($router->getRoutes()->getByName('spa'))->not->toBeNull();
        expect($router->getRoutes()->count())->toBe(1);
        foreach ($router->getRoutes() as $route) {
            expect($route->uri())->not->toContain('__review', '__fixtures', 'ui-review');
        }
    } finally {
        Route::swap($originalRouter);
        $this->app->instance('env', $originalEnvironment);
    }
})->with(['local', 'testing', 'staging', 'production']);

it('returns real 404s for retired preview paths and test-only harnesses', function () {
    config(['app.debug' => false]);
    foreach (['/__review/ui', '/__review/email', '/__review/errors/503', '/__review/errors/emergency', '/__review/application/overview', '/ui-review/index.html', '/__fixtures/ui', '/__fixtures/email'] as $path) {
        $this->get($path)->assertNotFound();
    }
    expect(app('view')->getFinder()->getHints())->not->toHaveKey('review-mail')->not->toHaveKey('browser-mail');
});
