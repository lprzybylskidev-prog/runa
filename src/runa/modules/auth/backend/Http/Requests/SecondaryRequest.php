<?php

namespace Runa\Modules\Auth\Http\Requests;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Runa\Contracts\Modules\OperationContext;

final class SecondaryRequest extends FormRequest
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
        $operation = $context?->operation;

        return ['version' => ['required', 'integer', 'min:1'], 'address_id' => [$operation === 'secondary.add_own' ? 'sometimes' : 'required', 'uuid:7'], 'email' => [$operation === 'secondary.remove_own' ? 'sometimes' : 'required', 'string', 'email', 'max:254', 'regex:/\A[\x21-\x7e]+\z/D']];
    }
}
