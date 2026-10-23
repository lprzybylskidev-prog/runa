<?php

namespace Runa\Support\Modules\Api;

use ReflectionNamedType;
use Runa\Contracts\Modules\ApiDefinition;
use Runa\Contracts\Modules\ApiProvider;
use Runa\Support\Modules\ModuleRecord;
use Runa\Support\Modules\ModuleRegistry;
use RuntimeException;

final readonly class ModuleApis
{
    public function __construct(private ModuleRegistry $registry, private ModuleDeclarations $declarations, private OperationGuard $guard, private OperationScope $scope) {}

    /** @template T of object
     * @param  class-string<T>  $contract
     * @return T
     */
    public function resolve(string $module, string $contract, string $version): object
    {
        [$record, $definition] = $this->definition($module, $contract, $version);
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
        [$record, $definition] = $this->definition($module, $contract, $version);
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
    }

    /** @return array{ModuleRecord, ApiDefinition} */
    private function definition(string $module, string $contract, string $version): array
    {
        try {
            $record = $this->registry->snapshot()->modules[$module] ?? throw new RuntimeException('Module missing.');
            $found = null;
            foreach ($this->declarations->classes($record, 'apis') as $class) {
                $provider = $this->declarations->make($record, $class, ApiProvider::class);
                if (! $provider instanceof ApiProvider) {
                    throw new RuntimeException('Unsupported public API descriptor.');
                }
                $definition = $provider->definition();
                $this->validate($record, $definition);
                if ($definition->contract === $contract) {
                    if ($found !== null) {
                        throw new RuntimeException('Duplicate public API contract.');
                    }
                    $found = $definition;
                }
            }
            if ($found === null || $found->version !== $version) {
                throw new OperationRejected('api_contract_unavailable', $module, 'unknown', 409);
            }

            return [$record, $found];
        } catch (OperationRejected $error) {
            throw $error;
        } catch (\Throwable) {
            throw new OperationRejected('api_contract_unavailable', $module, 'unknown');
        }
    }

    private function validate(ModuleRecord $record, ApiDefinition $definition): void
    {
        if ($definition->schemaVersion !== 1 || preg_match('/\A(0|[1-9][0-9]{0,8})\.(0|[1-9][0-9]{0,8})\.(0|[1-9][0-9]{0,8})\z/D', $definition->version) !== 1) {
            throw new RuntimeException('Unsupported public API version.');
        }
        $contract = $this->declarations->inspect($record, $definition->contract);
        $manifest = $this->declarations->manifest($record);
        $proxy = $this->declarations->inspect($record, $definition->proxy);
        $implementation = $this->declarations->inspect($record, $definition->implementation);
        if (! $contract->isInterface() || ! str_starts_with($contract->name, $manifest->prefix.'Contracts\\')
            || ! $proxy->isFinal() || ! $proxy->isSubclassOf(GuardedApi::class) || ! $proxy->implementsInterface($contract->name)
            || ! $implementation->isInstantiable() || ! $implementation->implementsInterface($contract->name)
            || count($definition->operations) < 1 || count($definition->operations) > 128) {
            throw new RuntimeException('Invalid typed API boundary.');
        }
        $methods = [];
        foreach ($contract->getMethods() as $method) {
            if ($method->isStatic() || $method->returnsReference() || str_starts_with($method->name, '__')) {
                throw new RuntimeException('Unsupported public API method.');
            }
            $methods[] = $method->name;
            $this->valueType($record, $method->getReturnType());
            foreach ($method->getParameters() as $parameter) {
                if ($parameter->isPassedByReference()) {
                    throw new RuntimeException('Public API arguments cannot be references.');
                }
                $this->valueType($record, $parameter->getType());
            }
        }
        if (array_diff($methods, array_keys($definition->operations)) !== [] || array_diff(array_keys($definition->operations), $methods) !== []) {
            throw new RuntimeException('Every public API method requires an operation declaration.');
        }

    }

    private function valueType(ModuleRecord $record, ?\ReflectionType $type): void
    {
        if (! $type instanceof ReflectionNamedType) {
            throw new RuntimeException('Public API types must be explicit named value types.');
        }
        if ($type->isBuiltin()) {
            if (! in_array($type->getName(), ['string', 'int', 'float', 'bool', 'array', 'null', 'void', 'never'], true)) {
                throw new RuntimeException('Public API cannot expose untyped objects or callbacks.');
            }

            return;
        }
        $class = $this->declarations->inspect($record, $type->getName());
        $prefix = $this->declarations->manifest($record)->prefix.'Contracts\\';
        if (! str_starts_with($class->name, $prefix) || (! $class->isReadOnly() && ! $class->isEnum())) {
            throw new RuntimeException('Public API DTOs must be owned readonly values.');
        }
    }
}
