<?php

namespace Runa\Modules\Admin\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Only source-owned Admin projections may reach this transport. */
final class AdminResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        if (! is_array($this->resource) || strlen(json_encode($this->resource, JSON_THROW_ON_ERROR)) > 1048576) {
            throw new \LogicException('Admin projection exceeds its transport boundary.');
        }

        return $this->resource;
    }
}
