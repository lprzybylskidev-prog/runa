<?php

namespace Runa\Modules\Auth\Contracts;

use Runa\Contracts\Modules\ApiOperation;

final readonly class DeliveryCategory
{
    public function __construct(public string $key, public array $names, public bool $mandatory = false, public bool $sensitive = false)
    {
        new ApiOperation($key);
        if (array_keys($names) !== ['pl', 'en'] || count(array_filter($names, fn ($name) => is_string($name) && trim($name) !== '' && strlen($name) <= 256)) !== 2) {
            throw new \InvalidArgumentException('Invalid delivery category.');
        }
    }
}
