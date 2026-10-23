<?php

namespace Runa\Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['application' => ['required', 'string', 'regex:/\A[a-zA-Z0-9][a-zA-Z0-9_.-]{0,127}\z/D'],
            'module' => ['required_if:operation,write', 'nullable', 'string', 'max:128'], 'view' => ['required_if:operation,write', 'required_with:key', 'string', 'max:128'],
            'key' => ['required_if:operation,write', 'string', 'max:128'], 'revision' => ['required_unless:operation,snapshot', 'integer', 'min:0'],
            'operation' => ['required', 'in:snapshot,write,reset,adopt'], 'value' => ['present_if:operation,write'],
            'values' => ['present_if:operation,adopt', 'array', 'max:32'],
            'values.*' => ['array:module,view,key,value'], 'values.*.module' => ['required', 'in:runa.auth'],
            'values.*.view' => ['required', 'in:global,navigation'], 'values.*.key' => ['required', 'in:locale,theme,collapsed,expanded'],
            'values.*.value' => ['present']];
    }
}
