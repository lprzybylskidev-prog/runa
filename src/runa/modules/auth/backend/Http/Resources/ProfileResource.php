<?php

namespace Runa\Modules\Auth\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Only owning Actions construct this private, allowlisted array projection. */
final class ProfileResource extends JsonResource
{
    /**
     * @return array<array-key, mixed>
     */
    public function toArray(Request $request): array
    {
        $value = $this->resource;
        if (! is_array($value)) {
            throw new \LogicException('Profile resource requires an owned array projection.');
        }

        return $value;
    }
}
