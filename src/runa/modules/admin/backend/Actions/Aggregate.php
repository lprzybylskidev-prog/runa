<?php

namespace Runa\Modules\Admin\Actions;

use Illuminate\Validation\ValidationException;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\TransactionalMutation;
use Runa\Integration\Core\AdminEntry;
use Runa\ModuleFramework\Api\OperationGuard;
use Runa\ModuleFramework\Api\PrincipalScope;
use Runa\Modules\Admin\Queries\Projection;
use Runa\Modules\Auth\Contracts\IdentityConflict;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Data\ModuleTransactions;

/** Coordinates declarations only. Owning mutations retain validation, persistence and Audit. */
final readonly class Aggregate
{
    public function __construct(private AdminEntry $entry, private Projection $projection,
        private ModuleTransactions $transactions, private PrincipalScope $principals, private OperationGuard $guard) {}

    /** @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function run(string $owner, string $page, array $input, bool $preview = false): array
    {
        // End entry admission before native health preflight/root locking.
        $this->entry->read(static fn () => null);
        $plan = $this->plan($owner, $page, $input, $preview);
        if ($preview) {
            return ['revision' => $plan['fingerprint'], 'participants' => $plan['reviews']];
        }
        $guard = new AggregateMutation($this, $this->projection, $owner, $page, $input, $plan['fingerprint'], $plan['parts'], $plan['mutations']);
        try {
            $results = $this->transactions->run([$guard, ...$plan['mutations']]);

            return $results['runa.admin.aggregate.save'];
        } catch (ValidationException) {
            // Owning late validation cannot expose private messages or foreign field paths.
            throw ValidationException::withMessages(['participants' => ['Invalid input.']]);
        } catch (IdentityConflict $error) {
            throw new OperationRejected($error->getMessage(), 'runa.admin', 'save', 409);
        } catch (OperationRejected $error) {
            throw $error;
        } catch (\Throwable) {
            throw new OperationRejected('admin_save_failed', 'runa.admin', 'save');
        }
    }

    /** Reauthorize entrance inside the root before any returned protected value. */
    public function assertResultAuthority(): void
    {
        $this->guard->enter('runa.admin', new ApiOperation('save', 'runa.admin.enter'));
    }

    /** @param array<string,mixed> $input
     * @return array{fingerprint:string,parts:list<AggregatePart>,mutations:list<TransactionalMutation>,reviews:list<array<string,mixed>>}
     */
    public function plan(string $owner, string $page, array $input, bool $preview = false): array
    {
        $selection = $this->projection->prepareAggregate($owner, $page, $input, $preview);
        $reviews = $signatures = $mutations = [];
        $requiresReview = false;
        foreach ($selection['parts'] as $part) {
            $review = $part->review();
            $mutation = $part->participant->prepare($part->input);
            $definition = $mutation->definition();
            $operation = $part->command->safety->operation;
            $access = array_filter($definition->access, fn ($access): bool => $access->module === ($part->owner === 'app' ? 'runa.admin' : $part->owner) && $access->operation == $operation);
            if ($definition->owner !== $part->owner || $access === [] || $review->requirement !== $part->command->safety->stepUp
                || count($definition->proofs) !== ($review->requirement === 'none' ? 0 : 1)) {
                throw new OperationRejected('admin_participant_incompatible', 'runa.admin', 'save', 409);
            }
            if ($review->requirement !== 'none') {
                $proofs = array_filter($definition->proofs, fn ($proof): bool => $proof->action === $part->command->proofAction
                    && $proof->requirement === $review->requirement && $proof->everyAction === $review->everyAction);
                if ($proofs === []) {
                    throw new OperationRejected('admin_participant_incompatible', 'runa.admin', 'save', 409);
                }
            }
            if (! $preview && $part->command->safety->previewRequired
                && (! is_string($part->input->review['fingerprint'] ?? null) || ! hash_equals($review->fingerprint, $part->input->review['fingerprint']))) {
                throw new OperationRejected('admin_review_stale', 'runa.admin', 'save', 409);
            }
            $requiresReview = $requiresReview || $part->command->safety->previewRequired;
            $reviews[] = ['id' => $part->id, 'review' => $part->reviewView($review)];
            $values = $part->input->values;
            ksort($values);
            $signatures[] = [$part->owner, $part->id, $part->input->command, $part->input->publicId,
                $part->input->revision, $values, $review->fingerprint, $definition];
            $mutations[] = $mutation;
        }
        $fingerprint = hash('sha256', serialize([$owner, $page, $this->principals->current()?->publicId, $selection['revision'], $signatures]));
        $expected = ! $preview && $requiresReview ? $fingerprint : $selection['revision'];
        if (! hash_equals($expected, $input['revision']) && ($preview || $requiresReview || ! hash_equals($fingerprint, $input['revision']))) {
            throw new OperationRejected('admin_revision_stale', 'runa.admin', 'save', 409);
        }

        return ['fingerprint' => $fingerprint, 'parts' => $selection['parts'], 'mutations' => $mutations, 'reviews' => $reviews];
    }
}
