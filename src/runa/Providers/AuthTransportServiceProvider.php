<?php

namespace Runa\Providers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use Runa\Contracts\Modules\HttpAccountPolicy;
use Runa\Http\AuthAccountBridge;
use Runa\Integration\Core\PermissionGate;
use Runa\Modules\Auth\Actions\AuthenticateCredentials;

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
        Gate::before(fn (?Authenticatable $user, string $ability, array $arguments = []): ?bool => $this->app->make(PermissionGate::class)->before($user, $ability, $arguments));
        Fortify::authenticateUsing(fn (Request $request) => $this->app->make(AuthenticateCredentials::class)->user($request));
    }
}
