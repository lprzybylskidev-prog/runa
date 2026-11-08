<?php

namespace App\Modules\Reference\Http;

use Illuminate\Foundation\Http\FormRequest;

/** Request shape only; current grants, records, reviews and proof remain owning API checks. */
final class ReferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string,list<string>> */
    public function rules(): array
    {
        $operation = $this->route()?->defaults['_runa_operation'] ?? null;

        return match ($operation) {
            'samples.edit' => [
                'public_id' => ['required', 'uuid:7'], 'revision' => ['required', 'string', 'regex:/\A[1-9][0-9]{0,18}\z/D'],
                'code' => ['required', 'string', 'max:40', 'regex:/\A[a-z][a-z0-9_-]*\z/D'],
            ],
            'settings.review', 'settings.publish' => [
                'values' => ['required', 'array:label,credential', 'min:1'],
                'values.label' => ['sometimes', 'required', 'string', 'max:40'],
                'values.credential' => ['sometimes', 'nullable', 'string', 'max:512'],
                'revision' => ['required', 'integer', 'min:0'],
                'review_id' => ['required', 'string', 'regex:/\A[a-f0-9]{64}\z/D'],
                'review' => [$operation === 'settings.publish' ? 'required' : 'prohibited', 'string', 'max:4096'],
            ],
            'snapshots.read' => ['public_id' => ['sometimes', 'nullable', 'uuid:7']],
            'snapshots.capture', 'snapshots.delete' => [
                'name' => [$operation === 'snapshots.capture' ? 'required' : 'prohibited', 'string', 'max:80'],
                'public_id' => [$operation === 'snapshots.delete' ? 'required' : 'prohibited', 'uuid:7'],
                'revision' => ['required', 'integer', 'min:0'],
                'review_id' => ['required', 'string', 'regex:/\A[a-f0-9]{64}\z/D'],
            ],
            'history.read' => ['sample' => ['sometimes', 'nullable', 'uuid:7'], 'before' => ['sometimes', 'nullable', 'uuid:7']],
            default => [],
        };
    }
}
