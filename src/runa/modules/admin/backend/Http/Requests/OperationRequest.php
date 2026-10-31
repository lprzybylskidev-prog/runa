<?php

namespace Runa\Modules\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class OperationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Owning adapter validates command-specific keys and exact scalar types. @return array<string,list<string>> */
    public function rules(): array
    {
        return ['export' => ['sometimes', 'boolean'], 'module' => ['sometimes', 'string', 'max:128'], 'id' => ['sometimes', 'uuid:7'], 'cursor' => ['sometimes', 'uuid:7'],
            'filter' => ['sometimes', 'string', 'in:enabled,registered,all'],
            'generation' => ['sometimes', 'integer', 'min:1'], 'revision' => ['sometimes', 'integer', 'min:0'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'review' => ['sometimes', 'string', 'max:32768'], 'intent' => ['sometimes', 'in:enable,disable,unregister'],
            'key' => ['sometimes', 'string', 'max:128'], 'enabled' => ['sometimes', 'boolean'],
            'confirmed' => ['sometimes', 'boolean'], 'patch' => ['sometimes', 'array'],
            'images' => ['sometimes', 'array:logo,logo_icon,favicon'],
            'images.*' => ['string', 'max:700000'],
            'config' => ['sometimes', 'array'], 'overrides' => ['sometimes', 'array']];
    }

    /** @return array<string,mixed> */
    public function operationInput(): array
    {
        $input = $this->isMethod('GET') ? $this->query() : $this->json()->all();
        if ($this->isMethod('GET') && isset($input['limit']) && is_string($input['limit']) && preg_match('/\A[0-9]{1,4}\z/D', $input['limit'])) {
            $input['limit'] = (int) $input['limit'];
        }

        if ($this->isMethod('GET') && isset($input['export']) && in_array($input['export'], ['0', '1'], true)) {
            $input['export'] = $input['export'] === '1';
        }

        return $input;
    }
}
