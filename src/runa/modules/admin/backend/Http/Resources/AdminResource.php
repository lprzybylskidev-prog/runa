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

        /** @var array<string,mixed> $projection Guarded Admin root projections are field maps. */
        $projection = $this->resource;

        return $this->wireMaps($projection);
    }

    /** JSON maps remain objects even when empty; PHP guarded consumers retain native arrays.
     *
     * @template TKey of array-key
     *
     * @param  array<TKey,mixed>  $value
     * @return array<TKey,mixed>
     */
    private function wireMaps(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                // Business JSON inside a map is opaque; do not reinterpret its field names.
                $value[$key] = in_array($key, ['values', 'changes', 'errors'], true)
                    ? ($item === [] ? (object) [] : $item) : $this->wireMaps($item);
            }
        }

        return $value;
    }
}
