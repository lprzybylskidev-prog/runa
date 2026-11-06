<?php

namespace Runa\ModuleFramework\Api;

use ReflectionNamedType;
use Runa\Contracts\Modules\ApiDefinition;
use Runa\Contracts\Modules\ApiProvider;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\Support\Modules\Api\GuardedApi;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\ModuleRecord;
use RuntimeException;

/** Internal shared source resolver. Never constructs a business implementation. */
final readonly class ApiDefinitions
{
    public function __construct(private ModuleRegistry $registry, private ModuleDeclarations $declarations) {}

    /** @return array{ModuleRecord, ApiDefinition} */
    public function get(string $module, string $contract, string $version): array
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
