<?php

namespace Runa\Modules\Auth\Support;

use Closure;

final class EntryPassword
{
    public static function rules(): array
    {
        return ['required', 'string', 'min:12', 'max:72', 'confirmed', function (string $attribute, mixed $value, Closure $fail): void {
            if (is_string($value) && (strlen($value) > 72 || str_contains($value, "\0"))) {
                $fail('Invalid password length or content.');
            }
        }];
    }
}
