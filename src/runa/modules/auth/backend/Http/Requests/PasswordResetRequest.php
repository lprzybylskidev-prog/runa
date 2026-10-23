<?php

namespace Runa\Modules\Auth\Http\Requests;

use Runa\Modules\Auth\Support\EntryPassword;

final class PasswordResetRequest extends TokenRequest
{
    public function rules(): array
    {
        return [...parent::rules(), 'password' => EntryPassword::rules()];
    }
}
