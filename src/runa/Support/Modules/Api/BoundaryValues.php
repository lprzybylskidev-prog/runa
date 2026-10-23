<?php

namespace Runa\Support\Modules\Api;

use ReflectionClass;
use RuntimeException;
use UnitEnum;

/** Also inspect nested arrays/DTOs: readonly alone does not make contained objects immutable. */
final class BoundaryValues
{
    public static function check(mixed $value, string $prefix, int $depth = 0): void
    {
        if ($depth > 32) {
            throw new RuntimeException('Public API value nesting exceeds boundary.');
        }
        if ($value === null || is_scalar($value)) {
            return;
        }
        if (is_object($value) && str_starts_with($value::class, $prefix)) {
            if ($value instanceof UnitEnum) {
                return;
            }
            $reflection = new ReflectionClass($value);
            if (! $reflection->isReadOnly()) {
                throw new RuntimeException('Public API objects must be readonly DTOs.');
            }
            foreach ($reflection->getProperties() as $property) {
                if ($property->isStatic() || ! $property->isPublic() || ! $property->isInitialized($value)) {
                    throw new RuntimeException('Public API DTO properties must be initialized public values.');
                }
                self::check($property->getValue($value), $prefix, $depth + 1);
            }

            return;
        }
        if (! is_array($value) || count($value) > 10000) {
            throw new RuntimeException('Public API values cannot expose mutable objects or resources.');
        }
        foreach ($value as $entry) {
            self::check($entry, $prefix, $depth + 1);
        }
    }
}
