<?php

namespace Runa\Contracts\Modules;

use InvalidArgumentException;

final readonly class EventDefinition
{
    public function __construct(public string $key, public int $version, public int $schemaVersion = 1)
    {
        new ApiOperation($key);
        if ($version < 1 || $version > 10000 || $schemaVersion !== 1) {
            throw new InvalidArgumentException('Invalid public event version.');
        }
    }
}
