<?php

namespace Runa\Modules\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class BootstrapRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Fixed route middleware and the guarded API both recheck entry authority.
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [];
    }
}
