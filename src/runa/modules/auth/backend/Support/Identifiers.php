<?php

namespace Runa\Modules\Auth\Support;

final class Identifiers
{
    /** Retain null and exact request text after validation, without coercing payload types. */
    public static function optionalText(mixed $value): ?string
    {
        if ($value !== null && ! is_string($value)) {
            throw new \InvalidArgumentException('Expected text or null.');
        }

        return $value;
    }

    public static function email(string $value): string
    {
        $value = strtolower(trim($value));
        if (strlen($value) > 254 || filter_var($value, FILTER_VALIDATE_EMAIL) === false || preg_match('/[^\x21-\x7e]/', $value)) {
            throw new \InvalidArgumentException('Invalid primary email.');
        }

        return $value;
    }

    public static function login(string $value): string
    {
        $value = strtolower(trim($value));
        if (preg_match('/\A[a-z0-9][a-z0-9._-]{0,63}\z/D', $value) !== 1) {
            throw new \InvalidArgumentException('Invalid login.');
        }

        return $value;
    }
}
