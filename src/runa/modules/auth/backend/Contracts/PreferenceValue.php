<?php

namespace Runa\Modules\Auth\Contracts;

use Runa\Support\Modules\Api\BoundaryValues;

final readonly class PreferenceValue
{
    public function __construct(public mixed $value)
    {
        BoundaryValues::check($value, "\0");
        if (strlen(json_encode($value, JSON_THROW_ON_ERROR)) > 32768) {
            throw new \InvalidArgumentException('Preference value too large.');
        }
    }
}
