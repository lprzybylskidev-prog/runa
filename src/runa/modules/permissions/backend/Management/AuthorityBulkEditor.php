<?php

namespace Runa\Modules\Permissions\Management;

use Runa\Contracts\Modules\ManagementOutcome;
use Runa\Contracts\Modules\ManagementPageResult;
use Runa\Contracts\Modules\ManagementRead;
use Runa\Contracts\Modules\ManagementRecord;
use Runa\Contracts\Modules\ManagementResourceReader;
use Runa\Contracts\Modules\ManagementReview;
use Runa\Contracts\Modules\ManagementWrite;
use Runa\Contracts\Modules\ManagementWriter;
use Runa\ModuleFramework\Management\InputValues as Values;
use Runa\Modules\Permissions\Contracts\AuthorityBatchPreview;
use Runa\Modules\Permissions\Contracts\PermissionsAuthorityBatch;
use Runa\Modules\Permissions\Contracts\UserSelection;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationRejected;

final readonly class AuthorityBulkEditor implements ManagementResourceReader, ManagementWriter
{
    public function __construct(private ModuleApis $apis) {}

    public function read(ManagementRead $input): ManagementRecord
    {
        return new ManagementRecord(null, '0', ['public_ids' => [], 'summary' => null]);
    }

    public function page(ManagementRead $input): ManagementPageResult
    {
        throw $this->invalid();
    }

    private function native(ManagementWrite $input): AuthorityBatchPreview
    {
        if ($input->publicId !== null || $input->revision !== '0' || array_keys($input->values) !== ['public_ids'] || ! in_array($input->command, ['grant_superadmin', 'revoke_superadmin'], true)) {
            throw $this->invalid();
        }
        try {
            return $this->api()->previewBatch(new UserSelection(Values::strings($input->values['public_ids'])), $input->command === 'grant_superadmin');
        } catch (\InvalidArgumentException|\TypeError) {
            throw $this->invalid();
        }
    }

    public function preview(ManagementWrite $input): ManagementReview
    {
        $review = $this->native($input);
        $changed = count(array_filter($review->targets, fn ($target): bool => $target->current !== $review->grant && ! in_array($target->userPublicId, $review->skippedPublicIds, true)));

        return new ManagementReview($review->fingerprint, ['summary' => [
            'accounts' => count($review->targets), 'affected_accounts' => $changed, 'unchanged_accounts' => count($review->targets) - $changed - count($review->skippedPublicIds),
            'skipped_accounts' => count($review->skippedPublicIds), 'ineligible_accounts' => 0,
        ]], 'password', true);
    }

    public function execute(ManagementWrite $input): ManagementOutcome
    {
        $review = $this->native($input);
        if (! $input->confirmed || ! is_string($input->review['fingerprint'] ?? null) || ! hash_equals($review->fingerprint, $input->review['fingerprint'])) {
            throw new OperationRejected('access_preview_stale', 'runa.permissions', 'authority.batch_apply', 409);
        }
        $this->api()->applyBatch($review);

        return new ManagementOutcome('committed', $this->read(new ManagementRead));
    }

    private function api(): PermissionsAuthorityBatch
    {
        return $this->apis->resolve('runa.permissions', PermissionsAuthorityBatch::class, '1.0.0');
    }

    private function invalid(): OperationRejected
    {
        return new OperationRejected('admin_input_invalid', 'runa.permissions', 'authority.batch_preview', 422);
    }
}
