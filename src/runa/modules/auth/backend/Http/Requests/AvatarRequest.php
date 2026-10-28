<?php

namespace Runa\Modules\Auth\Http\Requests;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Runa\Contracts\Modules\OperationContext;

final class AvatarRequest extends FormRequest
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
        $context = $this->attributes->get('runa.operation');
        if ($context !== null && ! $context instanceof OperationContext) {
            throw new \LogicException('Invalid guarded operation context.');
        }

        return ['version' => ['required', 'integer', 'min:1'], 'public_id' => [str_starts_with($context->operation ?? '', 'avatar.admin_') ? 'required' : 'sometimes', 'uuid:7'], 'avatar' => ['sometimes', 'file', 'max:5120']];
    }
}
