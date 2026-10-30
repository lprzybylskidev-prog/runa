<?php

namespace Runa\Modules\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

final class AggregateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (strlen($this->getContent()) > 262144) {
            throw ValidationException::withMessages(['participants' => ['Invalid input.']]);
        }
    }

    /** @return array<string,list<string>> */
    public function rules(): array
    {
        return ['revision' => ['required', 'string', 'max:256'], 'participants' => ['required', 'array', 'min:1', 'max:16'],
            'participants.*' => ['required', 'array:id,input'], 'participants.*.id' => ['required', 'string', 'distinct', 'max:512'],
            'participants.*.input' => ['required', 'array:command,public_id,revision,values,review,confirmed'],
            'participants.*.input.command' => ['required', 'string', 'max:128'],
            'participants.*.input.public_id' => ['present', 'nullable', 'uuid:7'],
            'participants.*.input.revision' => ['required', 'string', 'max:256'],
            'participants.*.input.values' => ['present', 'array', 'max:64'],
            'participants.*.input.review' => ['sometimes', 'nullable', 'array'],
            'participants.*.input.confirmed' => ['sometimes', 'boolean']];
    }
}
