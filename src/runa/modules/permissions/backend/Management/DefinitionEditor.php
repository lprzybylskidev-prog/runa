<?php

namespace Runa\Modules\Permissions\Management;

use Runa\Contracts\Modules\DictionaryEntry;
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
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\ModuleFramework\Management\InputValues as Values;
use Runa\Modules\Permissions\Contracts\AssignmentSet;
use Runa\Modules\Permissions\Contracts\DefinitionInput;
use Runa\Modules\Permissions\Contracts\DefinitionPreview;
use Runa\Modules\Permissions\Contracts\PermissionsAccess;
use Runa\Modules\Permissions\Contracts\PermissionsAuthority;
use Runa\Modules\Permissions\Contracts\RoleDeletionPreview;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationRejected;

abstract readonly class DefinitionEditor implements ManagementOptionsReader, ManagementResourceReader, ManagementWriter
{
    protected const string KIND = 'role';

    public function __construct(protected ModuleApis $apis) {}

    public function read(ManagementRead $input): ManagementRecord
    {
        if ($input->publicId === null) {
            return new ManagementRecord(null, '0', ['code' => static::KIND === 'permission' ? null : 'custom.', 'name_pl' => null, 'name_en' => null, 'description_pl' => null, 'description_en' => null,
                'roles' => [], 'permissions' => [], 'module' => null, 'system' => false, 'review_summary' => null]);
        }

        return $this->record($this->api()->definition(static::KIND, $input->publicId));
    }

    /** @param array{public_id:string,version:int,dictionary:array<string,string>,module:?string,system:bool,assigned:array{roles:list<string>,permissions:list<string>}} $state */
    protected function record(array $state): ManagementRecord
    {
        return new ManagementRecord($state['public_id'], (string) $state['version'], $state['dictionary'] + $state['assigned'] + [
            'module' => $state['module'], 'system' => $state['system'], 'review_summary' => null]);
    }

    public function page(ManagementRead $input): ManagementPageResult
    {
        if (count($input->sort) > 1) {
            throw $this->invalid();
        }
        try {
            if ($input->cursor !== null) {
                new TrustedPrincipal($input->cursor);
            }
        } catch (\InvalidArgumentException) {
            throw $this->invalid();
        }
        $result = $this->api()->definitions(static::KIND, $input->cursor, $input->filters, $input->sort[0]['key'] ?? null, $input->sort[0]['direction'] ?? 'asc');
        $items = array_slice($result['items'], 0, $input->limit);
        $next = $items !== [] && count($items) < count($result['items']) ? $items[array_key_last($items)]['public_id'] : $result['next'];

        return new ManagementPageResult(array_map(fn ($row) => new ManagementRecord($row['public_id'], (string) $row['version'], ['code' => $row['code'], 'name_pl' => $row['name_pl'], 'name_en' => $row['name_en']]), $items), $next, $result['total']);
    }

    public function options(ManagementOptionsRead $input): ManagementOptions
    {
        return app(AssignmentEditor::class)->options($input);
    }

    /** @return DefinitionPreview|RoleDeletionPreview|array<string,mixed> */
    private function native(ManagementWrite $input): DefinitionPreview|RoleDeletionPreview|array
    {
        try {
            if (static::KIND === 'permission' || ! in_array($input->command, ['store', 'delete', 'force_delete'], true)
                || preg_match('/\A[0-9]{1,9}\z/D', $input->revision) !== 1
                || ($input->publicId === null ? $input->revision !== '0' : (int) $input->revision < 1)) {
                throw $this->invalid();
            }
            $v = $input->values;
            if ($input->command !== 'store') {
                if ($v !== [] || $input->publicId === null) {
                    throw $this->invalid();
                }
                if ($input->command === 'force_delete') {
                    if (static::KIND !== 'role') {
                        throw $this->invalid();
                    }

                    return $this->authority()->previewRoleDeletion($input->publicId, (int) $input->revision);
                }
                $state = $this->api()->definition(static::KIND, $input->publicId);
                if ($state['version'] !== (int) $input->revision) {
                    throw $this->stale();
                }
                if ($state['module'] !== null || $state['system']) {
                    throw $this->invalid();
                }

                return $state;
            }
            $expected = ['code', 'name_pl', 'name_en', 'description_pl', 'description_en', 'permissions'];
            if (static::KIND === 'preset') {
                $expected = [...$expected, 'roles'];
            }
            $keys = array_keys($v);
            sort($keys);
            sort($expected);
            if ($keys !== $expected) {
                throw $this->invalid();
            }

            return $this->api()->previewDefinition(new DefinitionInput(static::KIND,
                new DictionaryEntry(Values::string($v['code']), Values::string($v['name_pl']), Values::string($v['name_en']), Values::string($v['description_pl']), Values::string($v['description_en'])),
                new AssignmentSet(Values::strings($v['roles'] ?? []), Values::strings($v['permissions'])), $input->publicId, $input->publicId === null ? null : (int) $input->revision));
        } catch (\InvalidArgumentException|\TypeError) {
            throw $this->invalid();
        }
    }

    public function preview(ManagementWrite $input): ManagementReview
    {
        $review = $this->native($input);
        $summary = $review instanceof DefinitionPreview ? ['before' => $review->before, 'after' => $review->after, 'affected_users' => $review->affectedUsers, 'permission_added' => $review->permissionAdded, 'permission_removed' => $review->permissionRemoved]
            : ($review instanceof RoleDeletionPreview ? ['users' => $review->users, 'presets' => $review->presets] : ['deleted_definition' => $review]);

        return new ManagementReview($this->fingerprint($review), ['review_summary' => $summary], 'password', true);
    }

    public function execute(ManagementWrite $input): ManagementOutcome
    {
        $native = $this->native($input);
        if (! is_string($input->review['fingerprint'] ?? null) || ! hash_equals($this->fingerprint($native), $input->review['fingerprint'])) {
            throw $this->stale();
        }
        if ($input->command !== 'store' && ! $input->confirmed) {
            throw $this->invalid();
        }
        if ($native instanceof DefinitionPreview) {
            return new ManagementOutcome('committed', $this->record($this->api()->saveDefinition($native)));
        }
        if ($native instanceof RoleDeletionPreview) {
            $this->authority()->forceDeleteRole($native, $input->confirmed);
        } else {
            $this->api()->deleteDefinition(static::KIND, Values::string($input->publicId), (int) $input->revision);
        }

        return new ManagementOutcome('committed', $this->read(new ManagementRead));
    }

    /** @param array<string,mixed> $review */
    private function fingerprint(DefinitionPreview|RoleDeletionPreview|array $review): string
    {
        return is_array($review) ? hash('sha256', serialize(['delete', static::KIND, $review])) : $review->fingerprint;
    }

    protected function api(): PermissionsAccess
    {
        return $this->apis->resolve('runa.permissions', PermissionsAccess::class, '2.2.0');
    }

    private function authority(): PermissionsAuthority
    {
        return $this->apis->resolve('runa.permissions', PermissionsAuthority::class, '1.0.0');
    }

    private function invalid(): OperationRejected
    {
        return new OperationRejected('admin_input_invalid', 'runa.permissions', 'access.preview_definition', 422);
    }

    private function stale(): OperationRejected
    {
        return new OperationRejected('access_preview_stale', 'runa.permissions', 'access.preview_definition', 409);
    }
}
