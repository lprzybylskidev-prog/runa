<?php

namespace Runa\Modules\Admin\Actions;

use Illuminate\Validation\ValidationException;
use Runa\Integration\Core\AdminEntry;
use Runa\Modules\Admin\Queries\Projection;
use Runa\Support\Modules\Api\OperationRejected;

/** Independent handlers retain their native transaction, Audit, proof and side-effect protocols. */
final readonly class Command
{
    public function __construct(private AdminEntry $entry, private Projection $projection) {}

    /** @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function run(string $owner, string $page, string $section, array $input, bool $preview = false): array
    {
        $plan = $this->entry->read(fn (): array => $this->projection->prepareCommand($owner, $page, $section, $input, $preview));
        // Never keep the entry transaction open around an independent native writer.
        try {
            if ($preview) {
                $review = $plan['writer']->preview($plan['input']);
                if ($review->requirement !== 'none' && $plan['command']->proofAction === null) {
                    throw new OperationRejected('admin_projection_invalid', 'runa.admin', 'preview');
                }
                $readable = array_filter($plan['fields'], fn ($field): bool => ! $field->field->sensitive);

                return ['fingerprint' => $review->fingerprint, 'changes' => array_intersect_key($review->changes, $readable),
                    'requirement' => $review->requirement, 'every_action' => $review->everyAction];
            }

            $outcome = $plan['writer']->execute($plan['input']);

            return $this->entry->read(fn (): array => $this->projection->commandOutcome($owner, $page, $section, $outcome));
        } catch (ValidationException $error) {
            $errors = [];
            foreach (array_keys($error->errors()) as $key) {
                if (in_array(explode('.', $key)[0], $plan['command']->safety->inputs, true)) {
                    $errors[$key] = ['Invalid input.'];
                }
            }
            throw ValidationException::withMessages($errors ?: ['command' => ['Invalid input.']]);
        } catch (OperationRejected $error) {
            throw $error;
        } catch (\Throwable) {
            throw new OperationRejected('admin_command_failed', 'runa.admin', 'command');
        }
    }
}
