<?php

namespace Runa\Contracts\Modules;

use InvalidArgumentException;

final readonly class SlotContribution
{
    public function __construct(public string $id, public SlotHost $target, public string $component, public int $priority = 0, public ?string $permission = null, public int $schemaVersion = 1)
    {
        new ApiOperation($id, $permission);
        new ApiOperation($component);
        if ($schemaVersion !== 1 || abs($priority) > 1000000) {
            throw new InvalidArgumentException('Invalid slot priority.');
        }
    }
}
