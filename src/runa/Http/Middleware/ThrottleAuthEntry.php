<?php

namespace Runa\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\RateLimiter;
use Runa\Contracts\Modules\AuthRequestThrottle;
use Runa\Support\Modules\Api\OperationRejected;
use Symfony\Component\HttpFoundation\Response;

/** Target source-owned abuse-prone operations; ordinary account work never consumes this cap. */
final readonly class ThrottleAuthEntry
{
    private const OPERATIONS = [
        'login', 'register', 'invitation.accept', 'verification.complete', 'verification.resend',
        'password.forgot', 'password.reset', 'password.change', 'step_up.confirm', 'trust.issue',
        'security.challenge', 'security.email_send', 'security.totp_begin', 'security.totp_confirm',
        'security.email_enable', 'security.disable', 'security.recovery_rotate',
    ];

    public function __construct(private AuthRequestThrottle $external) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();
        if (! $route instanceof Route || ($route->defaults['_runa_module'] ?? null) !== 'runa.auth') {
            return $next($request);
        }
        $operation = $route->defaults['_runa_operation'] ?? null;
        if ($request->isMethod('POST') && in_array($operation, self::OPERATIONS, true)) {
            // A future enabled module owns this request; never run both limiters.
            if (! $this->external->enforce($request, $operation) && $operation !== 'login') {
                // Login retains its transactional account/source credential defenses.
                $key = 'auth-entry:'.$operation.':'.hash('sha256', (string) $request->ip());
                if (RateLimiter::tooManyAttempts($key, 30)) {
                    throw new OperationRejected('too_many_requests', 'runa.auth', 'entry', 429, retryAfter: RateLimiter::availableIn($key));
                }
                RateLimiter::hit($key, 60);
            }
        }

        return $next($request)->setCache(['no_store' => true, 'private' => true]);
    }
}
