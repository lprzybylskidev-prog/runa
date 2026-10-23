<?php

namespace Runa\Support\Modules\Api;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Runa\Contracts\Modules\HttpAccess;
use Runa\Contracts\Modules\HttpAccountPolicy;
use Runa\Contracts\Modules\TrustedPrincipal;
use Symfony\Component\HttpFoundation\Response;

final readonly class ModuleHttpGuard
{
    public function __construct(private ModuleHttp $http, private OperationGuard $guard, private OperationScope $scope, private HttpAccountPolicy $accounts, private PrincipalScope $principals) {}

    /** @param Closure(Request): Response $next
     * Guards before route binding, Form Request resolution or controller construction. */
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();
        if (! $route instanceof Route) {
            throw new OperationRejected('operation_not_declared', 'unknown', 'unknown', 404);
        }
        $module = $route->defaults['_runa_module'] ?? '';
        $operation = $route->defaults['_runa_operation'] ?? '';
        if (! is_string($module) || ! is_string($operation)) {
            throw new OperationRejected('operation_not_declared', 'unknown', 'unknown', 404);
        }
        try {
            $endpoint = $this->http->endpoint($module, $operation);
            $expectedUri = 'api/'.str_replace('.', '/', $module).'/'.$endpoint->path;
            if ($route->uri() !== $expectedUri || $route->getActionName() !== ModuleHttpController::class.'@__invoke'
                || ($route->defaults['_runa_controller'] ?? null) !== $endpoint->controller
                || ($route->defaults['_runa_permission'] ?? null) !== $endpoint->operation->permission
                || ($route->defaults['_runa_access'] ?? 'public') !== $endpoint->access->value
                || ($request->method() !== $endpoint->method && ! ($endpoint->method === 'GET' && $request->isMethod('HEAD')))) {
                throw new OperationRejected('http_contract_changed', $module, $operation);
            }
            $principal = null;
            $context = $this->guard->enter($module, $endpoint->operation, function () use ($request, $module, $operation, $endpoint, &$principal): ?TrustedPrincipal {
                $principal = $this->accounts->principal($request, $module, $operation, $endpoint->access);
                if ($endpoint->access !== HttpAccess::Public && $principal === null) {
                    throw new OperationRejected('authentication_required', $module, $operation, 401);
                }

                return $principal;
            });
            $request->attributes->set('runa.operation', $context);
            // Reserved metadata must never be passed to controller method arguments.
            $route->forgetParameter('_runa_module');
            $route->forgetParameter('_runa_operation');
            $route->forgetParameter('_runa_controller');
            $route->forgetParameter('_runa_permission');
            $route->forgetParameter('_runa_access');
        } catch (OperationRejected $error) {
            throw $error;
        } catch (\Throwable) {
            throw new OperationRejected('http_contract_unavailable', $module, $operation);
        }

        return $this->principals->run($principal, fn (): Response => $this->scope->run($context, fn (): Response => $next($request)));
    }
}
