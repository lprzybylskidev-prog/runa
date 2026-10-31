<?php

namespace App\Modules\Reference\Definitions;

use Runa\Contracts\Modules\HealthCheck;
use Runa\Contracts\Modules\HealthContext;
use Runa\Contracts\Modules\HealthDefinition;
use Runa\Contracts\Modules\HealthPolicy;

final readonly class ReferenceHealthPolicy implements HealthPolicy
{
    public function schemaVersion(): int
    {
        return 2;
    }

    public function definition(): HealthDefinition
    {

        return new HealthDefinition('reference:1', 'hybrid', [new HealthCheck('writable_runtime', 'critical', 1, 60, 120)]);
    }

    public function check(string $check, HealthContext $context): bool
    {
        return $check === 'writable_runtime' && is_dir(storage_path('framework')) && is_writable(storage_path('framework'));
    }
}
