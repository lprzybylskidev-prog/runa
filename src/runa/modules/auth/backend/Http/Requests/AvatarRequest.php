<?php

namespace Runa\Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['version' => ['required', 'integer', 'min:1'], 'public_id' => [str_starts_with($this->attributes->get('runa.operation')?->operation ?? '', 'avatar.admin_') ? 'required' : 'sometimes', 'uuid:7'], 'avatar' => ['sometimes', 'file', 'max:5120']];
    }
}
