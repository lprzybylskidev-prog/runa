<?php

namespace Runa\Modules\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

final class CommandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (strlen($this->getContent()) > 262144) {
            throw ValidationException::withMessages(['command' => ['Invalid input.']]);
        }
    }

    /** @return array<string,list<string>> */
    public function rules(): array
    {
        return ['command' => ['required', 'string', 'max:128'],
            'public_id' => ['present', 'nullable', 'uuid:7'],
            'revision' => ['required', 'string', 'max:256'], 'values' => ['present', 'array', 'max:128'],
            'review' => ['sometimes', 'nullable', 'array'], 'confirmed' => ['sometimes', 'boolean']];
    }
}
