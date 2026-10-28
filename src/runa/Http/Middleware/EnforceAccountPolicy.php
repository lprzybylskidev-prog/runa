<?php

namespace Runa\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Runa\Contracts\Modules\HttpAccess;
use Runa\Contracts\Modules\HttpAccountPolicy;
use Runa\ModuleFramework\Api\PrincipalScope;
use Symfony\Component\HttpFoundation\Response;

/** Ordinary API routes share Auth's session predicates; module routes use their own adapter. */
final readonly class EnforceAccountPolicy
{
    public function __construct(private HttpAccountPolicy $accounts, private PrincipalScope $principals) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();
        if (! $route instanceof Route || isset($route->defaults['_runa_module']) || in_array($route->getName(), ['api.health', 'api.ready'], true)) {
            return $next($request);
        }
        $principal = $this->principals->resolve(fn () => $this->accounts->principal($request, null, $route->getName() ?? 'application', HttpAccess::Public));

        return $this->principals->run($principal, fn (): Response => $next($request));
    }
}
