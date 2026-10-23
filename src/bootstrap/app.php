<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Validation\ValidationException;
use Runa\Foundation\Application;
use Runa\Http\Middleware\AssignRequestId;
use Runa\Http\Middleware\EnforceAccountPolicy;
use Runa\Http\Middleware\EnforceEffectiveMaintenance;
use Runa\Http\Middleware\ThrottleAuthEntry;
use Runa\Support\Modules\Api\ModuleHttp;
use Runa\Support\Modules\Api\ModuleHttpGuard;
use Runa\Support\Modules\Console\ModuleConsole;
use Runa\Support\Modules\Frontend\ModuleFrontendRoutes;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
        $middleware->prependToPriorityList(SubstituteBindings::class, EnforceAccountPolicy::class);
        $middleware->api(prepend: [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ValidateCsrfToken::class,
        ], append: [ThrottleAuthEntry::class, EnforceAccountPolicy::class]);
        $middleware->append(AssignRequestId::class);
        $middleware->append(EnforceEffectiveMaintenance::class);
    })
    ->withSchedule(function (Schedule $schedule): void {
        app(ModuleConsole::class)->registerSchedules($schedule);
        $schedule->command('runa:runtime:pulse')->everyMinute()->evenInMaintenanceMode();
        $schedule->command('runa:modules:health')->everyMinute()->evenInMaintenanceMode();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['password', 'password_confirmation', 'current_password', 'token', 'identifier', 'email']);
        $exceptions->render(function (ValidationException $error, Request $request) {
            if ($request->is('api/runa/auth/*')) {
                $fields = array_fill_keys(array_keys($error->errors()), ['Invalid input.']);

                return response()->json(['error' => ['code' => 'validation_failed', 'fields' => $fields]], 422, ['Cache-Control' => 'no-store']);
            }
        });
        $exceptions->render(function (HttpException $error, Request $request) {
            if ($request->is('api/*') && $error->getStatusCode() === 419) {
                return response()->json(['error' => ['code' => 'csrf_token_mismatch']], 419, ['Cache-Control' => 'no-store']);
            }
        });
        $exceptions->render(function (Throwable $error, Request $request) {
            if ($request->is('api/runa/auth/*')
                && (! $error instanceof HttpExceptionInterface || $error->getStatusCode() >= 500)) {
                return response()->json(['error' => ['code' => 'auth_unavailable']], 503, ['Cache-Control' => 'no-store']);
            }
        });
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
