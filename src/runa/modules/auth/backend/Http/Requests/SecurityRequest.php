<?php

namespace Runa\Modules\Auth\Http\Requests;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class SecurityRequest extends FormRequest
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
        $operation = $this->route()?->getName() ?? throw new \LogicException('Security request requires a named route.');
        $confirmation = str_ends_with($operation, 'step_up.confirm');
        $login = str_ends_with($operation, 'challenge') || str_ends_with($operation, 'email_send');

        return [
            'current_password' => [$confirmation ? 'required' : 'sometimes', 'string', 'max:1024'],
            'method' => [$login ? 'sometimes' : 'prohibited', 'string', 'in:totp,recovery,email'],
            'code' => [$login ? 'sometimes' : 'prohibited', 'string', 'max:64'],
            'enrollment_code' => [str_ends_with($operation, 'totp_confirm') ? 'required' : 'sometimes', 'string', 'regex:/\A[0-9]{6}\z/D'],
            'target' => [str_ends_with($operation, 'disable') ? 'required' : 'sometimes', 'in:totp,email'],
            'action' => [str_ends_with($operation, 'step_up.confirm') ? 'required' : 'sometimes', 'string', 'regex:/\A[a-z][a-z0-9_.:-]{0,127}\z/D'],
            'requirement' => ['prohibited'],
        ];
    }
}
