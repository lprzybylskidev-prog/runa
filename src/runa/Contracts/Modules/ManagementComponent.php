<?php

namespace Runa\Contracts\Modules;

use InvalidArgumentException;

/** Build registry key only; never a URL, path, import expression or database code. */
final readonly class ManagementComponent
{
    public function __construct(public string $key, public string $component, public string $permission, public string $renderer = 'panel', public int $rendererVersion = 1, public int $schemaVersion = 1)
    {
        new ApiOperation($key, $permission);
        new ApiOperation($component);
        new ApiOperation($renderer);
        if ($schemaVersion !== 1 || $rendererVersion < 1) {
            throw new InvalidArgumentException('Unsupported management component schema.');
        }
    }
}
