<?php

namespace Runa\Modules\Auth\Http\Requests;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class EmailChangeRequest extends FormRequest
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
        return ['version' => ['required', 'integer', 'min:1'], 'current_password' => ['required', 'string', 'max:1024'], 'email' => ['required', 'string', 'email', 'max:254', 'regex:/\A[\x21-\x7e]+\z/D']];
    }
}
