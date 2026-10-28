<?php

namespace Runa\ModuleFramework\Api;

use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Runa\Contracts\Modules\OperationContext;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\Support\Modules\Api\OperationRejected;

/** Framework dispatcher prevents Laravel constructing module controllers before route guards. */
final readonly class ModuleHttpController
{
    public function __construct(private Container $container, private ModuleRegistry $registry, private ModuleDeclarations $declarations, private ModuleHttp $http, private OperationGuard $guard, private OperationScope $scope) {}

    public function __invoke(Request $request): mixed
    {
        $context = $request->attributes->get('runa.operation');
        $route = $request->route();
        if (! $context instanceof OperationContext || ! $route instanceof Route) {
            throw new OperationRejected('operation_not_declared', 'unknown', 'unknown', 404);
        }
        $this->guard->assertCurrent($context);
        $endpoint = $this->http->endpoint($context->moduleId, $context->operation);
        $record = $this->registry->snapshot()->modules[$context->moduleId];
        $controller = $this->declarations->make($record, $endpoint->controller);
        $this->guard->assertCurrent($context);
        if (! is_callable([$controller, '__invoke'])) {
            throw new OperationRejected('http_contract_unavailable', $context->moduleId, $context->operation);
        }

        return $this->scope->run($context, fn (): mixed => $this->container->call([$controller, '__invoke'], $route->parametersWithoutNulls()));
    }
}
