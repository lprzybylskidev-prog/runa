<?php

namespace Runa\Modules\Auth\Http\Requests;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Runa\Modules\Auth\Contracts\ProfileChange;
use Runa\Modules\Auth\Support\Identifiers;

final class ProfileRequest extends FormRequest
{
    /**
     * @return list<\Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            try {
                new ProfileChange(Identifiers::optionalText($this->input('name')), Identifiers::optionalText($this->input('first_name')), Identifiers::optionalText($this->input('surname')), Identifiers::optionalText($this->input('login')));
            } catch (\InvalidArgumentException) {
                $validator->errors()->add('name', 'Invalid naming or login.');
            }
        }];
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string|list<string|Rule|ValidationRule|\Closure|\Stringable>>
     */
    public function rules(): array
    {
        return ['version' => ['required', 'integer', 'min:1'], 'name' => ['nullable', 'string', 'max:160'], 'first_name' => ['nullable', 'string', 'max:160'], 'surname' => ['nullable', 'string', 'max:160'], 'login' => ['nullable', 'string', 'max:64']];
    }
}
