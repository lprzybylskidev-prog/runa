<?php

namespace Runa\Modules\Auth\Http;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Only owning Actions construct this private, allowlisted array projection. */
final class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->resource;
    }
}
