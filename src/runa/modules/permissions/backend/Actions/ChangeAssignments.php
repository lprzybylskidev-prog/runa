<?php

namespace Runa\Modules\Permissions\Actions;

use Illuminate\Database\Connection;
use Runa\Modules\Permissions\Contracts\AssignmentPreview;
use Runa\Modules\Permissions\Contracts\AssignmentSet;
use Runa\Modules\Permissions\Support\AccessState;
use Runa\Modules\Permissions\Support\AccessTransaction;

final readonly class ChangeAssignments
{
    public function __construct(private AccessState $state, private AccessTransaction $transactions) {}

    /** Called under the shared authority lock for a coherent reviewed snapshot. */
    public function preview(string $userPublicId, AssignmentSet $requested, string $mode, ?string $sourceUser = null, ?string $preset = null): AssignmentPreview
    {
        if (! in_array($mode, ['add', 'replace'], true) || ($sourceUser !== null && $preset !== null)) {
            throw new \InvalidArgumentException('Invalid assignment operation.');
        }
        $source = null;
        if ($sourceUser !== null) {
            $source = $this->state->user($sourceUser);
            $requested = new AssignmentSet(...$source['assigned']);
        } elseif ($preset !== null) {
            $source = $this->state->definition('preset', $preset);
            $requested = new AssignmentSet(...$source['assigned']);
        }
        $before = $this->state->user($userPublicId);
        $current = new AssignmentSet(...$before['assigned']);
        $after = $mode === 'replace' ? $requested : new AssignmentSet([...$current->roles, ...$requested->roles], [...$current->permissions, ...$requested->permissions]);
        $references = $this->state->references($requested);
        $this->state->references($after);
        $effectiveBefore = $before['eligible'] ? $this->state->effective($current, $before['super_admin']) : [];
        $effectiveAfter = $before['eligible'] ? $this->state->effective($after, $before['super_admin']) : [];
        $fingerprint = $this->state->fingerprint([$userPublicId, $mode, $sourceUser, $preset, $source, $before, $references, $after, $effectiveBefore, $effectiveAfter]);
        $added = $removed = [];
        foreach (['roles', 'permissions'] as $kind) {
            $added[$kind] = array_values(array_diff($after->$kind, $current->$kind));
            $removed[$kind] = array_values(array_diff($current->$kind, $after->$kind));
        }

        return new AssignmentPreview($userPublicId, $requested, $mode, $sourceUser, $preset, $fingerprint,
            $before['assigned'], $after->toArray(), $added, $removed,
            array_values(array_diff($effectiveAfter, $effectiveBefore)), array_values(array_diff($effectiveBefore, $effectiveAfter)));
    }

    public function apply(AssignmentPreview $reviewed): array
    {
        return $this->transactions->run(function (Connection $c) use ($reviewed): array {
            $current = $this->preview($reviewed->userPublicId, $reviewed->requested, $reviewed->mode, $reviewed->sourceUser, $reviewed->preset);
            if (! hash_equals($current->fingerprint, $reviewed->fingerprint)) {
                $this->state->reject('access_preview_stale', 409);
            }
            if ($current->before === $current->after) {
                return $this->state->user($current->userPublicId);
            }
            // Only ordinary roles change. Protected system assignment is preserved even in replace mode.
            foreach (['roles' => 'role', 'permissions' => 'permission'] as $kind => $singular) {
                $ids = $c->table('runa_permissions.'.$kind)->whereIn('public_id', $current->removed[$kind])->where('system', false)->pluck('id')->all();
                $c->table('runa_permissions.user_'.$kind)->where('user_public_id', $current->userPublicId)->whereIn($singular.'_id', $ids)->delete();
                $ids = $c->table('runa_permissions.'.$kind)->whereIn('public_id', $current->added[$kind])->where('system', false)->pluck('id')->all();
                foreach ($ids as $id) {
                    $c->table('runa_permissions.user_'.$kind)->insert(['user_public_id' => $current->userPublicId,
                        $singular.'_id' => $id, 'created_at' => now('UTC'), 'updated_at' => now('UTC')]);
                }
            }
            $revision = (int) ($c->table('runa_permissions.user_access')->where('user_public_id', $current->userPublicId)->value('version') ?? 0);
            $c->table('runa_permissions.user_access')->updateOrInsert(['user_public_id' => $current->userPublicId], ['version' => $revision + 1, 'updated_at' => now('UTC')]);
            // Explicit aggregate event: query-builder pivots emit no model events to suppress.
            $this->transactions->record('assignments.changed', 'user', $current->userPublicId, $current->before, $current->after,
                ['mode' => $current->mode, 'source_user' => $current->sourceUser, 'preset' => $current->preset, 'revision' => $revision + 1]);

            return $this->state->user($current->userPublicId);
        }, write: true);
    }
}
