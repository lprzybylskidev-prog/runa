<?php

namespace Runa\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Runa\Support\Modules\Api\OperationRejected;
use Symfony\Component\HttpFoundation\Response;

/** Small source cap before input/actions; account/source policy is extended by Auth's abuse flow. */
final class ThrottleAuthEntry
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('api/runa/auth/*')) {
            return $next($request);
        }
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            $key = 'auth-entry:'.hash('sha256', (string) $request->ip());
            if (RateLimiter::tooManyAttempts($key, 30)) {
                throw new OperationRejected('too_many_requests', 'runa.auth', 'entry', 429, retryAfter: RateLimiter::availableIn($key));
            }
            RateLimiter::hit($key, 60);
        }

        return $next($request)->setCache(['no_store' => true, 'private' => true]);
    }
}
