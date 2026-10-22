<?php

namespace Runa\Contracts\Modules;

use InvalidArgumentException;

/** Operational commands enqueue an owned job; diagnostics run a trusted owned handler. */
final readonly class CommandDefinition
{
    public function __construct(public string $key, public string $classification, public string $target, public int $schemaVersion = 1)
    {
        new ApiOperation($key);
        if ($schemaVersion !== 1 || ! in_array($classification, ['diagnostic', 'operational'], true) || $target === '') {
            throw new InvalidArgumentException('Invalid module command declaration.');
        }
        if ($classification === 'operational') {
            new ApiOperation($target);
        }
    }
}
