<?php

namespace Runa\Modules\Auth\Support;

final class Identifiers
{
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
