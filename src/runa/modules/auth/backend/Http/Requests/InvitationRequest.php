<?php

namespace Runa\Modules\Auth\Http\Requests;

use Runa\Modules\Auth\Support\EntryPassword;

final class InvitationRequest extends TokenRequest
{
    public function rules(): array
    {
        return [...parent::rules(), 'password' => EntryPassword::rules()];
    }
}
