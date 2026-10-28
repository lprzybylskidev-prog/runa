<?php

namespace Runa\Modules\Permissions\Actions;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\Modules\Permissions\Contracts\AssignmentPreview;
use Runa\Modules\Permissions\Contracts\AssignmentSet;
use Runa\Modules\Permissions\Contracts\DefinitionInput;
use Runa\Modules\Permissions\Contracts\DefinitionPreview;
use Runa\Modules\Permissions\Contracts\PermissionsAccess;
use Runa\Modules\Permissions\Support\AccessState;
use Runa\Modules\Permissions\Support\AccessTransaction;

/** Typed backend facade; no generic management executor or management UI. */
final readonly class ManageAccess implements PermissionsAccess
{
    public function __construct(private DatabaseManager $database, private AccessState $state, private AccessTransaction $transactions,
        private ChangeAssignments $assignments, private ChangeDefinitions $definitions) {}

    public function assignments(string $userPublicId): array
    {
        return $this->state->user($userPublicId);
    }

    public function previewAssignments(string $userPublicId, AssignmentSet $requested, string $mode = 'add'): AssignmentPreview
    {
        return $this->transactions->run(fn () => $this->assignments->preview($userPublicId, $requested, $mode));
    }

    public function previewCopy(string $sourceUser, string $userPublicId, string $mode = 'add'): AssignmentPreview
    {
        return $this->transactions->run(fn () => $this->assignments->preview($userPublicId, new AssignmentSet, $mode, sourceUser: $sourceUser));
    }

    public function previewPreset(string $preset, string $userPublicId, string $mode = 'add'): AssignmentPreview
    {
        return $this->transactions->run(fn () => $this->assignments->preview($userPublicId, new AssignmentSet, $mode, preset: $preset));
    }

    public function applyAssignments(AssignmentPreview $reviewed): array
    {
        return $this->assignments->apply($reviewed);
    }

    public function definitions(string $kind, ?string $after = null): array
    {
        $table = $this->state->table($kind);
        if ($after !== null) {
            new TrustedPrincipal($after);
        }
        /** @var Collection<int, \stdClass&object{public_id: string, code: string, name_pl: string, name_en: string, version: int}> $rows */
        $rows = $this->database->connection()->table('runa_permissions.'.$table)->whereNull('deleted_at')
            ->when($after !== null, fn ($q) => $q->where('public_id', '>', $after))->orderBy('public_id')->limit(101)->get(['public_id', 'code', 'name_pl', 'name_en', 'version']);
        $page = $rows->take(100);

        return ['items' => array_values($page->map(fn ($row) => ['public_id' => $row->public_id, 'code' => $row->code, 'name_pl' => $row->name_pl, 'name_en' => $row->name_en, 'version' => $row->version])->all()), 'next' => $rows->count() > 100 ? $page->last()?->public_id : null];
    }

    public function definition(string $kind, string $publicId): array
    {
        return $this->state->definition($kind, $publicId);
    }

    public function previewDefinition(DefinitionInput $input): DefinitionPreview
    {
        return $this->transactions->run(fn () => $this->definitions->preview($input));
    }

    public function saveDefinition(DefinitionPreview $reviewed): array
    {
        return $this->definitions->save($reviewed);
    }

    public function deleteDefinition(string $kind, string $publicId, int $version): void
    {
        $this->definitions->delete($kind, $publicId, $version);
    }
}
