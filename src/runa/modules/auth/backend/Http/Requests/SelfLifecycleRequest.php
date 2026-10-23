<?php

namespace Runa\Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SelfLifecycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['version' => ['required', 'integer', 'min:1'], 'operation' => ['required', 'in:deactivate,delete']];
    }
}
