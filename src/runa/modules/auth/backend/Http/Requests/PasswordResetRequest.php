<?php

namespace Runa\Modules\Auth\Http\Requests;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Runa\Modules\Auth\Support\EntryPassword;

final class PasswordResetRequest extends TokenRequest
{
    /**
     * @return array<string, string|list<string|Rule|ValidationRule|\Closure|\Stringable>>
     */
    public function rules(): array
    {
        return [...parent::rules(), 'password' => EntryPassword::rules()];
    }
}
