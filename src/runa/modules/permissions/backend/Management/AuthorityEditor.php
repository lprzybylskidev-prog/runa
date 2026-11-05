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
use Runa\ModuleFramework\Api\PrincipalScope;
use Runa\Modules\Permissions\Contracts\AuthorityPreview;
use Runa\Modules\Permissions\Contracts\PermissionsAuthority;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationRejected;

final readonly class AuthorityEditor implements ManagementResourceReader, ManagementWriter
{
    public function __construct(private ModuleApis $apis, private PrincipalScope $principals) {}

    public function read(ManagementRead $input): ManagementRecord
    {
        $id = $input->publicId ?? $this->principals->current()?->publicId ?? throw $this->invalid();
        $state = $this->api()->previewAuthority($id, false);

        return new ManagementRecord($id, (string) $state->revision, ['grant' => $state->current, 'review_summary' => null]);
    }

    public function page(ManagementRead $input): ManagementPageResult
    {
        throw $this->invalid();
    }

    private function native(ManagementWrite $input): AuthorityPreview
    {
        if ($input->command !== 'change' || $input->publicId === null || array_keys($input->values) !== ['grant'] || ! is_bool($input->values['grant'])) {
            throw $this->invalid();
        }
        $review = $this->api()->previewAuthority($input->publicId, $input->values['grant']);
        if ((string) $review->revision !== $input->revision) {
            throw $this->stale();
        }

        return $review;
    }

    public function preview(ManagementWrite $input): ManagementReview
    {
        $review = $this->native($input);

        return new ManagementReview($review->fingerprint, ['review_summary' => ['current' => $review->current, 'grant' => $review->grant, 'eligible' => $review->eligible, 'revision' => $review->revision]], 'password', true);
    }

    public function execute(ManagementWrite $input): ManagementOutcome
    {
        $review = $this->native($input);
        if (! $input->confirmed) {
            throw $this->invalid();
        }
        if (! is_string($input->review['fingerprint'] ?? null) || ! hash_equals($review->fingerprint, $input->review['fingerprint'])) {
            throw $this->stale();
        }
        $this->api()->applyAuthority($review);

        return new ManagementOutcome('committed');
    }

    private function api(): PermissionsAuthority
    {
        return $this->apis->resolve('runa.permissions', PermissionsAuthority::class, '1.0.0');
    }

    private function invalid(): OperationRejected
    {
        return new OperationRejected('admin_input_invalid', 'runa.permissions', 'authority.preview', 422);
    }

    private function stale(): OperationRejected
    {
        return new OperationRejected('access_preview_stale', 'runa.permissions', 'authority.preview', 409);
    }
}
