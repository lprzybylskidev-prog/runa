<?php

namespace Runa\Modules\Permissions\Management;

use Runa\Contracts\Modules\ManagementOptions;
use Runa\Contracts\Modules\ManagementOptionsRead;
use Runa\Contracts\Modules\ManagementOptionsReader;
use Runa\Contracts\Modules\ManagementOutcome;
use Runa\Contracts\Modules\ManagementPageResult;
use Runa\Contracts\Modules\ManagementRead;
use Runa\Contracts\Modules\ManagementRecord;
use Runa\Contracts\Modules\ManagementResourceReader;
use Runa\Contracts\Modules\ManagementReview;
use Runa\Contracts\Modules\ManagementWrite;
use Runa\Contracts\Modules\ManagementWriter;
use Runa\ModuleFramework\Api\PrincipalScope;
use Runa\Modules\Permissions\Contracts\AssignmentPreview;
use Runa\Modules\Permissions\Contracts\AssignmentSet;
use Runa\Modules\Permissions\Contracts\PermissionsAccess;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationRejected;

final readonly class AccessEditor implements ManagementOptionsReader, ManagementResourceReader, ManagementWriter
{
    public function __construct(private ModuleApis $apis, private PrincipalScope $principals) {}

    public function read(ManagementRead $input): ManagementRecord
    {
        $id = $input->publicId ?? $this->principals->current()?->publicId ?? throw $this->invalid();
        $state = $this->api()->assignments($id);

        return new ManagementRecord($id, hash('sha256', serialize($state)), $state['assigned'] + ['mode' => 'add', 'source_user' => null, 'preset' => null, 'review_summary' => null]);
    }

    public function page(ManagementRead $input): ManagementPageResult
    {
        throw $this->invalid();
    }

    public function options(ManagementOptionsRead $input): ManagementOptions
    {
        return app(AssignmentEditor::class)->options($input);
    }

    private function native(ManagementWrite $input): AssignmentPreview
    {
        try {
            $expected = match ($input->command) {
                'assign' => ['roles', 'permissions', 'mode'], 'copy' => ['source_user', 'mode'], 'preset' => ['preset', 'mode'], default => throw $this->invalid()
            };
            $keys = array_keys($input->values);
            sort($keys);
            sort($expected);
            if ($input->publicId === null || $keys !== $expected) {
                throw $this->invalid();
            }
            if (! hash_equals($this->read(new ManagementRead($input->publicId))->revision, $input->revision)) {
                throw $this->stale();
            }
            $v = $input->values;

            return match ($input->command) {
                'assign' => $this->api()->previewAssignments($input->publicId, new AssignmentSet($v['roles'], $v['permissions']), $v['mode']),
                'copy' => $this->api()->previewCopy($v['source_user'], $input->publicId, $v['mode']),
                'preset' => $this->api()->previewPreset($v['preset'], $input->publicId, $v['mode']),
            };
        } catch (\InvalidArgumentException|\TypeError) {
            throw $this->invalid();
        }
    }

    public function preview(ManagementWrite $input): ManagementReview
    {
        $review = $this->native($input);

        return new ManagementReview($review->fingerprint, ['review_summary' => ['before' => $review->before, 'after' => $review->after,
            'added' => $review->added, 'removed' => $review->removed, 'effective_added' => $review->effectiveAdded, 'effective_removed' => $review->effectiveRemoved]], 'both', true);
    }

    public function execute(ManagementWrite $input): ManagementOutcome
    {
        $native = $this->native($input);
        if (! is_string($input->review['fingerprint'] ?? null) || ! hash_equals($native->fingerprint, $input->review['fingerprint'])) {
            throw $this->stale();
        }
        $result = $this->api()->applyAssignments($native);

        return new ManagementOutcome('committed', $this->read(new ManagementRead($result['user_public_id'])));
    }

    private function api(): PermissionsAccess
    {
        return $this->apis->resolve('runa.permissions', PermissionsAccess::class, '2.0.0');
    }

    private function invalid(): OperationRejected
    {
        return new OperationRejected('admin_input_invalid', 'runa.permissions', 'access.preview_assignments', 422);
    }

    private function stale(): OperationRejected
    {
        return new OperationRejected('access_preview_stale', 'runa.permissions', 'access.preview_assignments', 409);
    }
}
