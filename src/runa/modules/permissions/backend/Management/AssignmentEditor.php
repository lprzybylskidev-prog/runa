<?php

namespace Runa\Modules\Permissions\Management;

use Runa\Contracts\Modules\ManagementOptions;
use Runa\Contracts\Modules\ManagementOptionsRead;
use Runa\Contracts\Modules\ManagementOptionsReader;
use Runa\Contracts\Modules\ManagementOutcome;
use Runa\Contracts\Modules\ManagementPageResult;
use Runa\Contracts\Modules\ManagementParticipant;
use Runa\Contracts\Modules\ManagementRead;
use Runa\Contracts\Modules\ManagementRecord;
use Runa\Contracts\Modules\ManagementResourceReader;
use Runa\Contracts\Modules\ManagementReview;
use Runa\Contracts\Modules\ManagementWrite;
use Runa\Contracts\Modules\ManagementWriter;
use Runa\Contracts\Modules\TransactionalMutation;
use Runa\ModuleFramework\Api\PrincipalScope;
use Runa\Modules\Permissions\Actions\ChangeAssignments;
use Runa\Modules\Permissions\Contracts\AssignmentPreview;
use Runa\Modules\Permissions\Contracts\AssignmentSet;
use Runa\Modules\Permissions\Contracts\PermissionsAccess;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Data\ModuleTransactions;

/** Ordinary assignment replacement only. Protected authority and bulk workflows remain independent. */
final readonly class AssignmentEditor implements ManagementOptionsReader, ManagementParticipant, ManagementResourceReader, ManagementWriter
{
    public function __construct(private ModuleApis $apis, private ChangeAssignments $assignments, private ModuleTransactions $transactions, private PrincipalScope $principals) {}

    public function read(ManagementRead $input): ManagementRecord
    {
        $publicId = $input->publicId ?? $this->principals->current()?->publicId;
        if ($publicId === null) {
            throw new OperationRejected('admin_input_invalid', 'runa.permissions', 'access.assignments', 422);
        }
        $state = $this->api()->assignments($publicId);

        return new ManagementRecord($publicId, hash('sha256', serialize($state)), $state['assigned']);
    }

    public function options(ManagementOptionsRead $input): ManagementOptions
    {
        if (! in_array($input->field, ['roles', 'permissions'], true) || $input->filters !== []) {
            throw new OperationRejected('admin_input_invalid', 'runa.permissions', 'access.definitions', 422);
        }
        $result = $this->api()->definitions($input->field === 'roles' ? 'role' : 'permission', $input->cursor);
        // The owning API pages at 100; respect the narrower consumer page without losing the cursor.
        $items = array_slice($result['items'], 0, $input->limit);
        $next = count($result['items']) > count($items) ? $items[array_key_last($items)]['public_id'] : $result['next'];

        $items = array_values(array_filter($items, fn ($item): bool => $input->field !== 'roles' || $item['code'] !== 'runa.permissions.super_admin'));

        return new ManagementOptions($input->field, array_map(fn ($item): array => ['value' => $item['public_id'], 'label' => $item['name_en']], $items), $next);
    }

    public function page(ManagementRead $input): ManagementPageResult
    {
        throw new OperationRejected('admin_operation_unavailable', 'runa.permissions', 'access.assignments', 404);
    }

    public function preview(ManagementWrite $input): ManagementReview
    {
        $review = $this->review($input);

        return new ManagementReview($review->fingerprint, [
            'roles' => ['old' => $review->before['roles'], 'new' => $review->after['roles']],
            'permissions' => ['old' => $review->before['permissions'], 'new' => $review->after['permissions']],
        ], 'both', true);
    }

    public function prepare(ManagementWrite $input): TransactionalMutation
    {
        $review = $this->review($input);
        if ($input->review !== null && (! is_string($input->review['fingerprint'] ?? null) || ! hash_equals($review->fingerprint, $input->review['fingerprint']))) {
            throw new OperationRejected('access_preview_stale', 'runa.permissions', 'access.apply_assignments', 409);
        }

        return $this->assignments->mutation($review);
    }

    public function execute(ManagementWrite $input): ManagementOutcome
    {
        if ($input->review === null) {
            throw new OperationRejected('admin_review_required', 'runa.permissions', 'access.apply_assignments', 409);
        }
        $mutation = $this->prepare($input);
        $result = $this->transactions->run([$mutation]);

        return $this->project($result['runa.permissions.'.$mutation->definition()->key]);
    }

    public function project(array $result): ManagementOutcome
    {
        return new ManagementOutcome('committed', $this->read(new ManagementRead($result['user_public_id'])));
    }

    private function review(ManagementWrite $input): AssignmentPreview
    {
        if ($input->command !== 'save' || $input->publicId === null || count($input->values) !== 2 || array_diff(array_keys($input->values), ['roles', 'permissions']) !== []) {
            throw new OperationRejected('admin_input_invalid', 'runa.permissions', 'access.preview_assignments', 422);
        }
        if (! hash_equals($this->read(new ManagementRead($input->publicId))->revision, $input->revision)) {
            throw new OperationRejected('access_preview_stale', 'runa.permissions', 'access.preview_assignments', 409);
        }
        try {
            $requested = new AssignmentSet($input->values['roles'], $input->values['permissions']);
        } catch (\InvalidArgumentException|\TypeError) {
            throw new OperationRejected('admin_input_invalid', 'runa.permissions', 'access.preview_assignments', 422);
        }

        return $this->api()->previewAssignments($input->publicId, $requested, 'replace');
    }

    private function api(): PermissionsAccess
    {
        return $this->apis->resolve('runa.permissions', PermissionsAccess::class, '2.0.0');
    }
}
