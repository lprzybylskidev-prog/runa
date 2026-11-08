<?php

namespace App\Modules\Reference\Http;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ReferenceResource extends JsonResource
{
    /** @param array<string,mixed> $resource */
    public function __construct(array $resource)
    {
        parent::__construct($resource);
    }

    /** @return array<string,mixed> */
    public function toArray(Request $request): array
    {
        /** @var array<string,mixed> $values */
        $values = $this->resource;

        return $values;
    }
}
