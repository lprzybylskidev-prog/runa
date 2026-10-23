<?php

namespace Runa\Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class DeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['operation' => ['required', 'in:read,write'], 'module' => ['required_if:operation,write', 'string', 'max:128'],
            'category' => ['required_if:operation,write', 'string', 'max:128'], 'revision' => ['required_if:operation,write', 'integer', 'min:0'],
            'enabled' => ['required_if:operation,write', 'boolean'], 'recipients' => ['required_if:operation,write', 'array', 'min:1', 'max:21'],
            'recipients.*' => ['string', 'distinct', 'max:64']];
    }
}
