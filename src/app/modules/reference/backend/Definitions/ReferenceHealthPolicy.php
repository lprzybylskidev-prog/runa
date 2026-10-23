<?php

namespace App\Modules\Reference\Definitions;

use InvalidArgumentException;
use Runa\Contracts\Modules\HealthCheck;
use Runa\Contracts\Modules\HealthContext;
use Runa\Contracts\Modules\HealthDefinition;
use Runa\Contracts\Modules\HealthPolicy;

final readonly class ReferenceHealthPolicy implements HealthPolicy
{
    public function schemaVersion(): int
    {
        return 1;
    }

    public function definition(array $overrides): HealthDefinition
    {
        if (array_diff(array_keys($overrides), ['mode']) !== [] || (isset($overrides['mode']) && ! in_array($overrides['mode'], ['on_use', 'scheduled', 'hybrid'], true))) {
            throw new InvalidArgumentException('The reference permits only a supported health mode override.');
        }

        return new HealthDefinition('reference:1', $overrides['mode'] ?? 'hybrid', [new HealthCheck('writable_runtime', 'critical', 1, 60, 120)]);
    }

    public function check(string $check, HealthContext $context): bool
    {
        return $check === 'writable_runtime' && is_dir(storage_path('framework')) && is_writable(storage_path('framework'));
    }
}
