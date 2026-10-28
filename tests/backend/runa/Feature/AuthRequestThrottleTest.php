<?php

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\RateLimiter as MemoryRateLimiter;
use Illuminate\Cache\Repository;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\RateLimiter;
use Runa\Contracts\Modules\AuthRequestThrottle;
use Runa\Http\Middleware\ThrottleAuthEntry;
use Runa\Integration\Core\NoAuthRequestThrottle;
use Runa\Support\Modules\Api\OperationRejected;
use Symfony\Component\HttpFoundation\JsonResponse;

function authThrottleRequest(string $operation, string $source = '192.0.2.10'): Request
{
    $request = Request::create('/api/runa/auth/diagnostic', 'POST', server: ['REMOTE_ADDR' => $source]);
    $route = new Route('POST', 'api/runa/auth/diagnostic', fn () => null);
    $route->defaults('_runa_module', 'runa.auth')->defaults('_runa_operation', $operation);
    $request->setRouteResolver(fn () => $route);

    return $request;
}

beforeEach(function (): void {
    RateLimiter::swap(new MemoryRateLimiter(new Repository(new ArrayStore)));
});

it('targeted auth request cap leaves normal account work usable after a protected operation exhausts its bucket', function (): void {
    $middleware = app(ThrottleAuthEntry::class);
    expect(app(AuthRequestThrottle::class))->toBeInstanceOf(NoAuthRequestThrottle::class);
    $next = fn () => new JsonResponse(['data' => []]);
    for ($n = 0; $n < 30; $n++) {
        $middleware->handle(authThrottleRequest('register'), $next);
    }
    foreach (['preferences.own', 'delivery.own', 'profile.edit', 'profile.history', 'avatar.upload', 'session.activity', 'logout', 'sessions.revoke'] as $operation) {
        for ($n = 0; $n < 35; $n++) {
            $response = $middleware->handle(authThrottleRequest($operation), $next);
            expect($response->getStatusCode())->toBe(200)
                ->and($response->headers->get('Cache-Control'))->toContain('no-store');
        }
    }
    // One operation/source cannot starve another operation or another source.
    expect($middleware->handle(authThrottleRequest('password.forgot'), $next)->getStatusCode())->toBe(200)
        ->and($middleware->handle(authThrottleRequest('register', '192.0.2.20'), $next)->getStatusCode())->toBe(200);
    expect(fn () => $middleware->handle(authThrottleRequest('register'), $next))->toThrow(OperationRejected::class);
});

it('targeted auth request cap protects public entry and proof attempts with 429 and retry information', function (string $operation): void {
    $middleware = app(ThrottleAuthEntry::class);
    $next = fn () => new JsonResponse;
    for ($n = 0; $n < 30; $n++) {
        $middleware->handle(authThrottleRequest($operation), $next);
    }
    try {
        $middleware->handle(authThrottleRequest($operation), $next);
        $this->fail('The protected operation exceeded its source cap.');
    } catch (OperationRejected $error) {
        expect($error->reason)->toBe('too_many_requests')->and($error->status)->toBe(429)
            ->and($error->retryAfter)->toBeGreaterThan(0)->toBeLessThanOrEqual(60)
            ->and($error->render()->headers->has('Retry-After'))->toBeTrue();
    }
})->with(['register', 'verification.complete', 'password.forgot', 'security.challenge', 'step_up.confirm']);

it('targeted auth request cap does not replace intrinsic login defenses or trust a path without module metadata', function (): void {
    $middleware = app(ThrottleAuthEntry::class);
    $next = fn () => new JsonResponse;
    for ($n = 0; $n < 35; $n++) {
        expect($middleware->handle(authThrottleRequest('login'), $next)->getStatusCode())->toBe(200);
        expect($middleware->handle(Request::create('/api/runa/auth/register', 'POST'), $next)->getStatusCode())->toBe(200);
    }
});

it('targeted auth request cap gives an active adapter exclusive ownership and restores local fallback immediately on disable', function (): void {
    $adapter = new class implements AuthRequestThrottle
    {
        public bool $active = true;

        public int $handled = 0;

        public function enforce(Request $request, string $operation): bool
        {
            if (! $this->active) {
                return false;
            }
            $this->handled++;

            return true;
        }
    };
    app()->instance(AuthRequestThrottle::class, $adapter);
    $middleware = app(ThrottleAuthEntry::class);
    $next = fn () => new JsonResponse;
    for ($n = 0; $n < 35; $n++) {
        $middleware->handle(authThrottleRequest('register'), $next);
    }
    $middleware->handle(authThrottleRequest('login'), $next);
    expect($adapter->handled)->toBe(36);
    $adapter->active = false;
    for ($n = 0; $n < 30; $n++) {
        $middleware->handle(authThrottleRequest('register'), $next);
    }
    expect(fn () => $middleware->handle(authThrottleRequest('register'), $next))->toThrow(OperationRejected::class)
        ->and($adapter->handled)->toBe(36);
});

it('targeted auth request cap propagates adapter rejection and failure without silently falling back', function (): void {
    $adapter = new class implements AuthRequestThrottle
    {
        public bool $broken = false;

        public function enforce(Request $request, string $operation): bool
        {
            if ($this->broken) {
                throw new RuntimeException('Throttle adapter unavailable.');
            }
            throw new OperationRejected('too_many_requests', 'runa.auth', 'entry', 429, retryAfter: 10);
        }
    };
    $middleware = new ThrottleAuthEntry($adapter);
    $called = false;
    $next = function () use (&$called): JsonResponse {
        $called = true;

        return new JsonResponse;
    };
    expect(fn () => $middleware->handle(authThrottleRequest('register'), $next))->toThrow(OperationRejected::class);
    $adapter->broken = true;
    expect(fn () => $middleware->handle(authThrottleRequest('register'), $next))->toThrow(RuntimeException::class)
        ->and($called)->toBeFalse()
        ->and(RateLimiter::attempts('auth-entry:register:'.hash('sha256', '192.0.2.10')))->toBe(0);
});
