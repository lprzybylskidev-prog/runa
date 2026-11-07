<?php

namespace App\Modules\Reference\Http;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ReferenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->resource;
    }
}
