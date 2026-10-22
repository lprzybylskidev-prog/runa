<?php

namespace Runa\Support\Modules\Api;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;

final readonly class ModuleHttpGuard
{
    public function __construct(private ModuleHttp $http, private OperationGuard $guard) {}

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
                || ($request->method() !== $endpoint->method && ! ($endpoint->method === 'GET' && $request->isMethod('HEAD')))) {
                throw new OperationRejected('http_contract_changed', $module, $operation);
            }
            $context = $this->guard->enter($module, $endpoint->operation);
            $request->attributes->set('runa.operation', $context);
            // Reserved metadata must never be passed to controller method arguments.
            $route->forgetParameter('_runa_module');
            $route->forgetParameter('_runa_operation');
            $route->forgetParameter('_runa_controller');
            $route->forgetParameter('_runa_permission');
        } catch (OperationRejected $error) {
            throw $error;
        } catch (\Throwable) {
            throw new OperationRejected('http_contract_unavailable', $module, $operation);
        }

        return $next($request);
    }
}
