<?php

namespace Runa\Contracts\Modules;

use InvalidArgumentException;

final readonly class MutationAccess
{
    public function __construct(public string $module, public ApiOperation $operation)
    {
        if (preg_match('/\A(?:runa|app)\.[a-z][a-z0-9_]{0,47}\z/D', $module) !== 1) {
            throw new InvalidArgumentException('Invalid mutation module.');
        }
    }
}
