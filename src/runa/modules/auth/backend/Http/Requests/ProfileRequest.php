<?php

namespace Runa\Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Runa\Modules\Auth\Contracts\ProfileChange;

final class ProfileRequest extends FormRequest
{
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            try {
                new ProfileChange($this->input('name'), $this->input('first_name'), $this->input('surname'), $this->input('login'));
            } catch (\InvalidArgumentException) {
                $validator->errors()->add('name', 'Invalid naming or login.');
            }
        }];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['version' => ['required', 'integer', 'min:1'], 'name' => ['nullable', 'string', 'max:160'], 'first_name' => ['nullable', 'string', 'max:160'], 'surname' => ['nullable', 'string', 'max:160'], 'login' => ['nullable', 'string', 'max:64']];
    }
}
