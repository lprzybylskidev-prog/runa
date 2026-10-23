<?php

namespace Runa\Contracts\Modules;

final readonly class ApiDefinition
{
    /** @param class-string $contract
     * @param  class-string  $proxy
     * @param  class-string  $implementation
     * @param  array<string, ApiOperation>  $operations  Interface method to shared operation identity.
     */
    public function __construct(public string $contract, public string $proxy, public string $implementation, public string $version, public array $operations, public int $schemaVersion = 1)
    {
        self::validateOperations($operations);
    }

    private static function validateOperations(mixed $operations): void
    {
        if (! is_array($operations)) {
            throw new \InvalidArgumentException('API operations require a map.');
        }
        foreach ($operations as $method => $operation) {
            if (! is_string($method) || ! $operation instanceof ApiOperation) {
                throw new \InvalidArgumentException('API operations require typed declarations.');
            }
        }
    }
}
