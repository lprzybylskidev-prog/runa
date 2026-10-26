<?php

namespace Runa\Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class HistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['kind' => ['required', 'in:login,account'], 'before' => ['sometimes', 'nullable', 'uuid:7']];
    }
}
