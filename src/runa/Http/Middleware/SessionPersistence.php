<?php

namespace Runa\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Inside StartSession: cookie persistence follows the admitted session, never a recaller. */
final class SessionPersistence
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } finally {
            // Also reset on anonymous/logout requests in reused applications.
            config()->set('session.expire_on_close', $request->session()->get('auth.remember') !== true);
        }
    }
}
