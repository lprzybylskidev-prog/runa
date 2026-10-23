<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Runa\Foundation\Application;
use Runa\Http\Middleware\AssignRequestId;
use Runa\Http\Middleware\EnforceEffectiveMaintenance;
use Runa\Support\Modules\Api\ModuleHttp;
use Runa\Support\Modules\Api\ModuleHttpGuard;
use Runa\Support\Modules\Console\ModuleConsole;
use Runa\Support\Modules\Frontend\ModuleFrontendRoutes;

/** @var list<class-string> $platformProviders */
$platformProviders = require __DIR__.'/providers.php';

$app = Application::configure(basePath: dirname(__DIR__))
    ->withProviders($platformProviders)
    ->withRouting(api: __DIR__.'/../runa/routes/api.php', then: function (): void {
        require __DIR__.'/../app/routes/web.php';
        app(ModuleHttp::class)->register();
        app(ModuleFrontendRoutes::class)->register();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->preventRequestsDuringMaintenance(except: ['api/health', 'api/ready']);
        $middleware->prependToPriorityList(SubstituteBindings::class, ModuleHttpGuard::class);
        $middleware->append(AssignRequestId::class);
        $middleware->append(EnforceEffectiveMaintenance::class);
    })
    ->withSchedule(function (Schedule $schedule): void {
        app(ModuleConsole::class)->registerSchedules($schedule);
        $schedule->command('runa:runtime:pulse')->everyMinute()->evenInMaintenanceMode();
        $schedule->command('runa:modules:health')->everyMinute()->evenInMaintenanceMode();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

// Private environment inputs remain outside the protected Laravel source tree.
$app->useEnvironmentPath(dirname(__DIR__, 2));
$app->useConfigPath(dirname(__DIR__).'/runa/config');
$app->useDatabasePath(dirname(__DIR__).'/app/database');
$app->usePublicPath(dirname(__DIR__).'/public');
$app->useLangPath(dirname(__DIR__).'/runa/lang');
$storage = $_ENV['LARAVEL_STORAGE_PATH'] ?? $_SERVER['LARAVEL_STORAGE_PATH'] ?? null;
$app->useStoragePath(is_string($storage) && $storage !== '' ? $storage : dirname(__DIR__).'/storage');

return $app;
