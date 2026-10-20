<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Runa\Foundation\Application;
use Runa\Http\Middleware\AssignRequestId;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(api: __DIR__.'/../runa/routes/api.php', then: function (): void {
        require __DIR__.'/../app/routes/web.php';
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(AssignRequestId::class);
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('runa:runtime:pulse')->everyMinute();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

// Compatibility paths for the staged frontend/tooling relocation in point 4.
$app->useEnvironmentPath(dirname(__DIR__, 2));
$app->useConfigPath(dirname(__DIR__).'/runa/config');
$app->useDatabasePath(dirname(__DIR__).'/app/database');
$app->usePublicPath(dirname(__DIR__, 2).'/public');
$app->useLangPath(dirname(__DIR__, 2).'/lang');
$storage = $_ENV['LARAVEL_STORAGE_PATH'] ?? $_SERVER['LARAVEL_STORAGE_PATH'] ?? null;
$app->useStoragePath(is_string($storage) && $storage !== '' ? $storage : dirname(__DIR__, 2).'/storage');

return $app;
