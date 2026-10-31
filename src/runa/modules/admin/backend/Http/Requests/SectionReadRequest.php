<?php

namespace Runa\Modules\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

final class SectionReadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'export' => ['sometimes', 'boolean'],
            'kind' => ['sometimes', 'required', 'string', 'in:record,page,options'],
            'field' => ['sometimes', 'required', 'string', 'max:128'],
            'public_id' => ['sometimes', 'required', 'uuid:7'],
            'cursor' => ['sometimes', 'required', 'string', 'max:2048'],
            'limit' => ['sometimes', 'required', 'integer', 'min:1', 'max:100'],
            // One JSON object preserves null/boolean/numeric filter semantics over GET.
            'filters' => ['sometimes', 'required', 'string', 'max:65536'],
            'sort' => ['sometimes', 'array', 'max:4'],
            'sort.*' => ['required', 'array:key,direction'],
            'sort.*.key' => ['required', 'string', 'max:128'],
            'sort.*.direction' => ['required', 'in:asc,desc'],
        ];
    }

    /** Keep unknown keys for the owning projector's fail-closed allowlist.
     * @return array<string, mixed>
     */
    public function readQuery(): array
    {
        $query = $this->query();
        if (isset($query['filters'])) {
            try {
                $filters = json_decode($query['filters'], false, 10, JSON_THROW_ON_ERROR);
                if (! $filters instanceof \stdClass) {
                    throw new \InvalidArgumentException('Invalid filter map.');
                }
                $query['filters'] = (array) $filters;
            } catch (\JsonException|\InvalidArgumentException) {
                throw ValidationException::withMessages(['filters' => ['Invalid input.']]);
            }
        }
        if (isset($query['export']) && in_array($query['export'], ['0', '1'], true)) {
            $query['export'] = $query['export'] === '1';
        }
        // GET scalars arrive as strings; never coerce malformed values or arrays.
        if (isset($query['limit']) && is_string($query['limit']) && preg_match('/\A[0-9]{1,3}\z/D', $query['limit']) === 1) {
            $query['limit'] = (int) $query['limit'];
        }

        return $query;
    }
}
