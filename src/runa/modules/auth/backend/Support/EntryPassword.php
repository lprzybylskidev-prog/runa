<?php

namespace Runa\Modules\Auth\Support;

use Closure;
use Illuminate\Validation\Rules\Password;

final class EntryPassword
{
    public static function rules(): array
    {
        $policy = app(CredentialPolicy::class);
        $rule = Password::min($policy->value('password_min_length'));
        if ($policy->value('password_mixed_case')) {
            $rule->mixedCase();
        }
        if ($policy->value('password_numbers')) {
            $rule->numbers();
        }
        if ($policy->value('password_symbols')) {
            $rule->symbols();
        }

        return ['required', 'string', $rule, 'max:72', 'confirmed', function (string $attribute, mixed $value, Closure $fail): void {
            if (is_string($value) && (strlen($value) > 72 || str_contains($value, "\0"))) {
                $fail('Invalid password length or content.');
            }
        }];
    }
}
