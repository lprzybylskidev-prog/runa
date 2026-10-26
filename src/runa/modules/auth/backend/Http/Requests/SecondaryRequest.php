<?php

namespace Runa\Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SecondaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $operation = $this->attributes->get('runa.operation')?->operation;

        return ['version' => ['required', 'integer', 'min:1'], 'address_id' => [$operation === 'secondary.add_own' ? 'sometimes' : 'required', 'uuid:7'], 'email' => [$operation === 'secondary.remove_own' ? 'sometimes' : 'required', 'string', 'email', 'max:254', 'regex:/\A[\x21-\x7e]+\z/D']];
    }
}
