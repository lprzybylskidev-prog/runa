<?php

namespace Runa\Support\Modules\Api;

use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Router;
use Runa\Contracts\Modules\HttpEndpoint;
use Runa\Contracts\Modules\HttpProvider;
use Runa\Support\Modules\ModuleRecord;
use Runa\Support\Modules\ModuleRegistry;
use RuntimeException;

final readonly class ModuleHttp
{
    public function __construct(private ModuleRegistry $registry, private ModuleDeclarations $declarations, private Router $router, private Connection $connection) {}

    /** Register all source-present routes, including disabled modules, without registry writes.
     * Route cache stores immutable identities; middleware reacquires declarations on every request.
     */
    public function register(): void
    {
        // A cold database has no registry yet; installation must not require runtime schema.
        try {
            if (! $this->connection->getSchemaBuilder()->hasTable('shared.module_registry_state')) {
                return;
            }
        } catch (\Throwable) {
            // Effective maintenance rejects runtime database failure; cold setup stays bootable.
            return;
        }
        $planned = [];
        $unavailable = [];
        $seen = [];
        foreach ($this->registry->snapshot()->modules as $record) {
            if (! $record->registered) {
                continue;
            }
            try {
                $endpoints = $this->endpoints($record);
            } catch (\Throwable) {
                // A stale/missing module cannot block sync, health recovery or independent modules.
                $unavailable[] = $record->id;

                continue;
            }
            foreach ($endpoints as $endpoint) {
                [$owner, $code] = explode('.', $record->id, 2);
                $uri = 'api/'.$owner.'/'.$code.'/'.$endpoint->path;
                $name = $record->id.'.'.$endpoint->operation->key;
                $methods = $endpoint->method === 'GET' ? ['GET', 'HEAD'] : [$endpoint->method];
                foreach ($methods as $method) {
                    $key = $method.' '.$uri;
                    if (isset($seen[$key])) {
                        throw new RuntimeException('Duplicate module HTTP path.');
                    }
                    $seen[$key] = true;
                    foreach ($this->router->getRoutes()->getRoutes() as $route) {
                        if ($route->uri() === $uri && in_array($method, $route->methods(), true)) {
                            throw new RuntimeException('Module HTTP path collides with existing route.');
                        }
                    }
                }
                if (isset($seen[$name]) || $this->router->getRoutes()->getByName($name) !== null) {
                    throw new RuntimeException('Duplicate module HTTP name.');
                }
                $seen[$name] = true;
                $planned[] = [$record, $endpoint, $uri, $name];
            }
        }
        foreach ($unavailable as $module) {
            $this->router->addRoute(['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], 'api/'.str_replace('.', '/', $module).'/{_runa_path?}', ModuleHttpUnavailable::class.'@__invoke')
                ->where('_runa_path', '.*')->name($module.'._unavailable')->defaults('_runa_module', $module);
        }
        foreach ($planned as [$record, $endpoint, $uri, $name]) {
            $this->router->addRoute($endpoint->method, $uri, ModuleHttpController::class.'@__invoke')
                ->name($name)->middleware(['api', ModuleHttpGuard::class])
                ->defaults('_runa_module', $record->id)->defaults('_runa_operation', $endpoint->operation->key)
                ->defaults('_runa_controller', $endpoint->controller)->defaults('_runa_permission', $endpoint->operation->permission);
        }
    }

    public function endpoint(string $module, string $operation): HttpEndpoint
    {
        $record = $this->registry->snapshot()->modules[$module] ?? throw new OperationRejected('module_unavailable', $module, $operation);
        foreach ($this->endpoints($record) as $endpoint) {
            if ($endpoint->operation->key === $operation) {
                return $endpoint;
            }
        }
        throw new OperationRejected('operation_not_declared', $module, $operation, 404);
    }

    /** @return list<HttpEndpoint> */
    private function endpoints(ModuleRecord $record): array
    {
        $result = [];
        $names = [];
        foreach ($this->declarations->classes($record, 'http') as $class) {
            $provider = $this->declarations->make($record, $class, HttpProvider::class);
            if (! $provider instanceof HttpProvider || $provider->schemaVersion() !== 1) {
                throw new RuntimeException('Unsupported HTTP descriptor.');
            }
            $endpoints = $this->validateList($provider->endpoints());
            if (count($result) + count($endpoints) > 128) {
                throw new RuntimeException('HTTP declarations must be a bounded list.');
            }
            foreach ($endpoints as $endpoint) {
                if (! in_array($endpoint->method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true)
                    || strlen($endpoint->path) > 256 || preg_match('/\A[a-z][a-z0-9-]*(?:\/(?:[a-z][a-z0-9-]*|\{[a-z][a-z0-9_]*\}))*\z/D', $endpoint->path) !== 1
                    || isset($names[$endpoint->operation->key])) {
                    throw new RuntimeException('Invalid or duplicate module HTTP endpoint.');
                }
                $controller = $this->declarations->inspect($record, $endpoint->controller);
                if (! $controller->isInstantiable() || ! $controller->hasMethod('__invoke') || ! $controller->getMethod('__invoke')->isPublic()
                    || $controller->hasMethod('getMiddleware') || $controller->implementsInterface(HasMiddleware::class)) {
                    throw new RuntimeException('HTTP endpoint requires an owned invokable controller.');
                }
                foreach ($controller->getMethod('__invoke')->getParameters() as $parameter) {
                    $type = $parameter->getType();
                    if (! $type instanceof \ReflectionNamedType
                        || ($type->isBuiltin() && ! in_array($type->getName(), ['string', 'int', 'float', 'bool'], true))
                        || (! $type->isBuiltin() && ! is_a($type->getName(), Request::class, true))) {
                        throw new RuntimeException('HTTP action parameters must be explicit route scalars or Requests; implicit model binding is unsupported.');
                    }
                }
                $names[$endpoint->operation->key] = true;
                $result[] = $endpoint;
            }
        }

        return $result;
    }

    /** @return list<HttpEndpoint> */
    private function validateList(mixed $endpoints): array
    {
        if (! is_array($endpoints) || ! array_is_list($endpoints)) {
            throw new RuntimeException('HTTP declarations must be a list.');
        }
        foreach ($endpoints as $endpoint) {
            if (! $endpoint instanceof HttpEndpoint) {
                throw new RuntimeException('HTTP declarations require typed endpoints.');
            }
        }

        return $endpoints;
    }
}
