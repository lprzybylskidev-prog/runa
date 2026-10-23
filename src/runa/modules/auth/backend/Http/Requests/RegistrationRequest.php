<?php

namespace Runa\Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Runa\Modules\Auth\Contracts\NewIdentity;
use Runa\Modules\Auth\Support\EntryPassword;

final class RegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'max:254'], 'login' => ['nullable', 'string', 'max:64'],
            'name' => ['nullable', 'string', 'max:160'], 'first_name' => ['nullable', 'string', 'max:160'], 'surname' => ['nullable', 'string', 'max:160'],
            'password' => EntryPassword::rules(),
            'active' => ['prohibited'], 'approved' => ['prohibited'], 'email_verified_at' => ['prohibited'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            try {
                new NewIdentity($this->input('email'), $this->input('name'), $this->input('first_name'), $this->input('surname'), $this->input('login'));
            } catch (\InvalidArgumentException) {
                $validator->errors()->add('identity', 'Supply valid identifiers and either a name or both first name and surname.');
            }
        }];
    }
}
