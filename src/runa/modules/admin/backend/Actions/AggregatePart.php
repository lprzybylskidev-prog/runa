<?php

namespace Runa\Modules\Admin\Actions;

use Illuminate\Validation\ValidationException;
use Runa\Contracts\Modules\ManagementCommandDefinition;
use Runa\Contracts\Modules\ManagementFieldAccess;
use Runa\Contracts\Modules\ManagementParticipant;
use Runa\Contracts\Modules\ManagementReview;
use Runa\Contracts\Modules\ManagementWrite;
use Runa\Contracts\Modules\ManagementWriter;

/** Internal prepared declaration; business state remains owned by the participant. */
final readonly class AggregatePart
{
    /** @param array<string,ManagementFieldAccess> $fields */
    public function __construct(public string $owner, public string $id, public ManagementCommandDefinition $command,
        public ManagementWrite $input, public ManagementWriter $writer, public ManagementParticipant $participant, public array $fields) {}

    public function review(): ManagementReview
    {
        try {
            return $this->writer->preview($this->input);
        } catch (ValidationException $error) {
            $errors = [];
            foreach (array_keys($error->errors()) as $key) {
                if (in_array(explode('.', $key)[0], $this->command->safety->inputs, true)) {
                    $errors[$this->id.'.'.$key] = ['Invalid input.'];
                }
            }
            throw ValidationException::withMessages($errors ?: [$this->id => ['Invalid input.']]);
        }
    }

    /** @return array<string,mixed> */
    public function reviewView(ManagementReview $review): array
    {
        $fields = array_filter($this->fields, fn ($field): bool => ! $field->field->sensitive);

        return ['fingerprint' => $review->fingerprint, 'changes' => array_intersect_key($review->changes, $fields),
            'requirement' => $review->requirement, 'every_action' => $review->everyAction];
    }
}
