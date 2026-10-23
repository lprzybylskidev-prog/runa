<?php

namespace Runa\Contracts\Modules;

use InvalidArgumentException;

final readonly class HealthDefinition
{
    /** @param list<HealthCheck> $checks */
    public function __construct(public string $revision, public string $mode, public array $checks)
    {
        if (preg_match('/\A[a-zA-Z0-9_.:-]{1,128}\z/D', $revision) !== 1 || ! in_array($mode, ['on_use', 'scheduled', 'hybrid'], true) || count($checks) > 64 || array_sum(array_map(fn (HealthCheck $check): int => $check->timeout, $checks)) > 60 || count(array_unique(array_map(fn (HealthCheck $check): string => $check->id, $checks))) !== count($checks)) {
            throw new InvalidArgumentException('Invalid module health policy.');
        }
    }
}
