<?php

namespace Runa\Modules\Auth\Management;

/** Adapt shared datetime controls to Auth's native whole-second UTC command values. */
final class Moment
{
    public static function seconds(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (! is_string($value)) {
            throw new \InvalidArgumentException('Expected a UTC moment.');
        }

        // Only zero fractions are removed; native values still validate calendar/precision.
        return preg_replace('/(?:\.0{1,6})?(?:Z|\+00:00)\z/D', 'Z', $value);
    }
}
