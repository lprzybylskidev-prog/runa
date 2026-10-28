<?php

namespace Runa\Modules\Auth\Http\Requests;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Runa\Modules\Auth\Support\EntryPassword;

final class PasswordChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string|list<string|Rule|ValidationRule|\Closure|\Stringable>>
     */
    public function rules(): array
    {
        return ['current_password' => ['required', 'string', 'max:1024'], 'password' => EntryPassword::rules(),
            'sessions' => ['sometimes', 'in:keep_all,revoke_others,revoke_all'],
            'method' => ['sometimes', 'in:totp,recovery,email'], 'code' => ['sometimes', 'string', 'max:64']];
    }
}
