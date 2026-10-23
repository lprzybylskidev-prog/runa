<?php

namespace Runa\Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['identifier' => ['required', 'string', 'max:254'], 'password' => ['required', 'string', 'max:1024'], 'remember' => ['prohibited']];
    }
}
