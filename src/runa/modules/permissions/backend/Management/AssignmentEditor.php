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
use Runa\Modules\Auth\Contracts\AuthAdministration;
use Runa\Modules\Auth\Contracts\UserDirectoryQuery;
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

        return new ManagementRecord($publicId, hash('sha256', serialize($state)), $state['assigned'] + ['source_user' => null, 'preset' => null, 'mode' => 'add']);
    }

    public function options(ManagementOptionsRead $input): ManagementOptions
    {
        if (! in_array($input->field, ['roles', 'permissions', 'preset', 'source_user'], true) || array_diff(array_keys($input->filters), ['search', 'language', 'selected']) !== []) {
            throw new OperationRejected('admin_input_invalid', 'runa.permissions', 'access.definitions', 422);
        }
        $language = $input->filters['language'] ?? 'en';
        $search = $input->filters['search'] ?? '';
        if (! in_array($language, ['pl', 'en'], true) || ! is_string($search) || mb_strlen($search) > 160) {
            throw new OperationRejected('admin_input_invalid', 'runa.permissions', 'access.definitions', 422);
        }
        if ($input->field === 'source_user') {
            if (isset($input->filters['selected'])) {
                throw new OperationRejected('admin_input_invalid', 'runa.permissions', 'access.definitions', 422);
            }
            $page = $this->apis->resolve('runa.auth', AuthAdministration::class, '2.1.0')->users(new UserDirectoryQuery($input->cursor, $input->limit, $search ?: null));

            return new ManagementOptions($input->field, array_map(fn ($user): array => ['value' => $user->publicId, 'label' => $user->displayName], $page->items), $page->next);
        }
        $filters = ['search' => $search];
        if (isset($input->filters['selected'])) {
            try {
                $ids = json_decode($input->filters['selected'], true, flags: JSON_THROW_ON_ERROR);
                if (! is_array($ids)) {
                    throw new \InvalidArgumentException('Invalid selected definitions.');
                }
                $filters['public_ids'] = (new AssignmentSet($ids))->roles;
            } catch (\JsonException|\InvalidArgumentException|\TypeError) {
                throw new OperationRejected('admin_input_invalid', 'runa.permissions', 'access.definitions', 422);
            }
        }
        $result = $this->api()->definitions(match ($input->field) {
            'roles' => 'role', 'preset' => 'preset', default => 'permission'
        }, $input->cursor, $filters);
        // The owning API pages at 100; respect the narrower consumer page without losing the cursor.
        $items = array_slice($result['items'], 0, $input->limit);
        $next = count($result['items']) > count($items) ? $items[array_key_last($items)]['public_id'] : $result['next'];

        $items = array_values(array_filter($items, fn ($item): bool => $input->field !== 'roles' || $item['code'] !== 'runa.permissions.super_admin'));

        return new ManagementOptions($input->field, array_map(fn ($item): array => ['value' => $item['public_id'], 'label' => $item['name_'.$language]], $items), $next);
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
        ], 'password', true);
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
        $expected = match ($input->command) {
            'save' => ['roles', 'permissions'],
            'copy' => ['source_user', 'mode'],
            'preset' => ['preset', 'mode'],
            default => throw new OperationRejected('admin_input_invalid', 'runa.permissions', 'access.preview_assignments', 422),
        };
        if ($input->publicId === null || count($input->values) !== count($expected) || array_diff(array_keys($input->values), $expected) !== []) {
            throw new OperationRejected('admin_input_invalid', 'runa.permissions', 'access.preview_assignments', 422);
        }
        if (! hash_equals($this->read(new ManagementRead($input->publicId))->revision, $input->revision)) {
            throw new OperationRejected('access_preview_stale', 'runa.permissions', 'access.preview_assignments', 409);
        }
        try {
            return match ($input->command) {
                'copy' => $this->api()->previewCopy($input->values['source_user'], $input->publicId, $input->values['mode']),
                'preset' => $this->api()->previewPreset($input->values['preset'], $input->publicId, $input->values['mode']),
                default => $this->api()->previewAssignments($input->publicId, new AssignmentSet($input->values['roles'], $input->values['permissions']), 'replace'),
            };
        } catch (\InvalidArgumentException|\TypeError) {
            throw new OperationRejected('admin_input_invalid', 'runa.permissions', 'access.preview_assignments', 422);
        }

    }

    private function api(): PermissionsAccess
    {
        return $this->apis->resolve('runa.permissions', PermissionsAccess::class, '2.2.0');
    }
}
