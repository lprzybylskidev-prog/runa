<?php

namespace Runa\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use Runa\Contracts\Modules\HttpAccountPolicy;
use Runa\Http\AuthAccountBridge;
use Runa\Modules\Auth\Actions\AuthenticateCredentials;
use Runa\Modules\Auth\Actions\RegisterAccount;

/** Package routes must be disabled before any provider boots, even on a cold install. */
final class AuthTransportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Fortify::ignoreRoutes();
        $this->app->bind(HttpAccountPolicy::class, AuthAccountBridge::class);
    }

    public function boot(): void
    {
        Fortify::createUsersUsing(RegisterAccount::class);
        Fortify::authenticateUsing(fn (Request $request) => $this->app->make(AuthenticateCredentials::class)->user($request));
    }
}
