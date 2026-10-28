<?php

namespace Runa\Modules\Auth\Http\Requests;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class DeliveryRequest extends FormRequest
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
        return ['operation' => ['required', 'in:read,write'], 'module' => ['required_if:operation,write', 'string', 'max:128'],
            'category' => ['required_if:operation,write', 'string', 'max:128'], 'revision' => ['required_if:operation,write', 'integer', 'min:0'],
            'enabled' => ['required_if:operation,write', 'boolean'], 'recipients' => ['required_if:operation,write', 'array', 'min:1', 'max:21'],
            'recipients.*' => ['string', 'distinct', 'max:64']];
    }
}
