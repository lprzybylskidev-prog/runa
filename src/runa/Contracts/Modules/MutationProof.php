<?php

namespace Runa\Contracts\Modules;

use InvalidArgumentException;

final readonly class MutationProof
{
    public function __construct(public string $action, public string $requirement, public int $ttlSeconds = 300, public bool $everyAction = true)
    {
        if (preg_match('/\A[a-z][a-z0-9_.:-]{0,127}\z/D', $action) !== 1
            || ! in_array($requirement, ['password', 'second_factor', 'both'], true) || $ttlSeconds < 1 || $ttlSeconds > 300) {
            throw new InvalidArgumentException('Invalid mutation proof.');
        }
    }
}
