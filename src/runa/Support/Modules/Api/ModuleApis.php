<?php

namespace Runa\Support\Modules\Api;

use Runa\ModuleFramework\Api\ApiDefinitions;
use Runa\ModuleFramework\Api\BoundaryValues;
use Runa\ModuleFramework\Api\ModuleDeclarations;
use Runa\ModuleFramework\Api\OperationGuard;
use Runa\ModuleFramework\Api\OperationScope;
use Runa\ModuleFramework\Transactions\HealthAdmissions;
use RuntimeException;

final readonly class ModuleApis
{
    public function __construct(private ApiDefinitions $definitions, private ModuleDeclarations $declarations, private OperationGuard $guard, private OperationScope $scope, private HealthAdmissions $admissions) {}

    /** @template T of object
     * @param  class-string<T>  $contract
     * @return T
     */
    public function resolve(string $module, string $contract, string $version): object
    {
        [$record, $definition] = $this->definitions->get($module, $contract, $version);
        $proxy = $definition->proxy;
        $instance = new $proxy($this, $module, $contract, $version);
        if (! $instance instanceof $contract) {
            throw new RuntimeException('Invalid API proxy.');
        }

        return $instance;
    }

    /** @param list<mixed> $arguments */
    public function invoke(string $module, string $contract, string $version, string $method, array $arguments): mixed
    {
        return $this->admissions->invoke([$module, $contract, $version, $method], function () use ($module, $contract, $version, $method, $arguments): mixed {
            [$record, $definition] = $this->definitions->get($module, $contract, $version);
            $this->admissions->assertReadDefinition($definition);
            $operation = $definition->operations[$method] ?? throw new OperationRejected('operation_not_declared', $module, 'unknown', 404);
            $context = $this->guard->enter($module, $operation);
            $prefix = $this->declarations->manifest($record)->prefix.'Contracts\\';
            BoundaryValues::check($arguments, $prefix);
            // Resolve an implementation only after ingress is admitted; never hand it to the caller.
            $result = $this->scope->run($context, function () use ($record, $definition, $context, $method, $arguments): mixed {
                $implementation = $this->declarations->make($record, $definition->implementation);
                $this->guard->assertCurrent($context);

                return $implementation->$method(...$arguments);
            });
            BoundaryValues::check($result, $prefix);

            return $result;
        });
    }
}
