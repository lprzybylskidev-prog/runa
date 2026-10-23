<?php

namespace Runa\Support\Modules\Data;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Runa\Contracts\Modules\PublicReferenceLookup;

final readonly class PublicReferenceRule implements ValidationRule
{
    public function __construct(private PublicReferenceLookup $lookup) {}

    public static function valid(mixed $value): bool
    {
        return is_string($value) && preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/Di', $value) === 1;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! self::valid($value) || ! $this->lookup->exists(strtolower($value))) {
            $fail('The :attribute must reference an available UUIDv7 resource.');
        }
    }
}
