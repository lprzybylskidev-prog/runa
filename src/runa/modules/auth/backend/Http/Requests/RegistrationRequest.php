<?php

namespace Runa\Modules\Auth\Http\Requests;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Runa\Modules\Auth\Contracts\NewIdentity;
use Runa\Modules\Auth\Support\EntryPassword;
use Runa\Modules\Auth\Support\Identifiers;

final class RegistrationRequest extends FormRequest
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
        return [
            'email' => ['required', 'string', 'max:254'], 'login' => ['nullable', 'string', 'max:64'],
            'name' => ['nullable', 'string', 'max:160'], 'first_name' => ['nullable', 'string', 'max:160'], 'surname' => ['nullable', 'string', 'max:160'],
            'password' => EntryPassword::rules(),
            'password_confirmation' => ['required', 'string', 'max:72'],
            'active' => ['prohibited'], 'approved' => ['prohibited'], 'email_verified_at' => ['prohibited'],
        ];
    }

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
                new NewIdentity($this->string('email')->toString(), Identifiers::optionalText($this->input('name')), Identifiers::optionalText($this->input('first_name')), Identifiers::optionalText($this->input('surname')), Identifiers::optionalText($this->input('login')));
            } catch (\InvalidArgumentException) {
                $validator->errors()->add('identity', 'Supply valid identifiers and either a name or both first name and surname.');
            }
        }];
    }
}
