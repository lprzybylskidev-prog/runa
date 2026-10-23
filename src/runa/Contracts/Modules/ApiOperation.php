<?php

namespace Runa\Contracts\Modules;

use InvalidArgumentException;

final readonly class ApiOperation
{
    public function __construct(public string $key, public ?string $permission = null)
    {
        foreach (array_filter([$key, $permission], fn ($value) => $value !== null) as $value) {
            if (preg_match('/\A[a-z][a-z0-9_]*(?:\.[a-z][a-z0-9_]*)*\z/D', $value) !== 1 || strlen($value) > 128) {
                throw new InvalidArgumentException('Invalid operation or permission key.');
            }
        }
    }
}
