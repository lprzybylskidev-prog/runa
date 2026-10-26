<?php

namespace Runa\Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['token' => ['required', 'string', 'regex:/\A[0-9a-f]{64}\z/D']];
    }
}
