<?php

namespace Runa\Modules\Permissions\Actions;

use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Runa\Modules\Auth\Contracts\AuthorizationIdentity;
use Runa\Modules\Permissions\Contracts\AssignmentSet;
use Runa\Modules\Permissions\Contracts\RoleDeletionPreview;
use Runa\Modules\Permissions\Support\AccessState;
use Runa\Modules\Permissions\Support\AccessTransaction;

/** Explicit same-schema unlinking; no cascading foreign keys or source/system deletion. */
final readonly class DeleteRoles
{
    public function __construct(private DatabaseManager $database, private AccessState $state,
        private AccessTransaction $transactions, private AuthorizationIdentity $identities) {}

    public function preview(string $publicId, int $version): RoleDeletionPreview
    {
        $before = $this->state->definition('role', $publicId);
        if ($before['system'] || $before['module'] !== null) {
            $this->state->reject('access_definition_protected', 403);
        }
        if ($before['version'] !== $version) {
            $this->state->reject('access_definition_stale', 409);
        }
        $c = $this->database->connection();
        $id = $c->table('runa_permissions.roles')->where('public_id', $publicId)->value('id');
        $members = $c->table('runa_permissions.user_roles')->where('role_id', $id)->orderBy('user_public_id')->limit(101)->get(['id', 'user_public_id']);
        $references = $c->table('runa_permissions.preset_roles as pr')->join('runa_permissions.presets as p', 'p.id', '=', 'pr.preset_id')
            ->where('role_id', $id)->orderBy('p.public_id')->limit(101)->get(['pr.id as membership_id', 'p.public_id', 'p.version']);
        if ($members->count() > 100 || $references->count() > 100) {
            $this->state->reject('access_deletion_limit');
        }
        $users = [];
        $binding = [];
        foreach ($members as $member) {
            // Projection includes deleted identities: retained pivots must also be removed safely.
            $user = $member->user_public_id;
            $eligibility = $this->identities->eligibility($user);
            $roleRows = $c->table('runa_permissions.user_roles as ur')->join('runa_permissions.roles as r', 'r.id', '=', 'ur.role_id')
                ->where('user_public_id', $user)->whereNull('r.deleted_at')->orderBy('r.public_id')->limit(102)->get(['r.public_id', 'r.system']);
            $direct = $c->table('runa_permissions.user_permissions as up')->join('runa_permissions.permissions as p', 'p.id', '=', 'up.permission_id')
                ->where('user_public_id', $user)->whereNull('p.deleted_at')->orderBy('p.public_id')->limit(101)->pluck('p.public_id')->all();
            $set = new AssignmentSet($roleRows->where('system', false)->pluck('public_id')->all(), $direct);
            $after = new AssignmentSet(array_values(array_diff($set->roles, [$publicId])), $direct);
            $super = $roleRows->contains('system', true);
            $old = $eligibility?->mayOperate() ? $this->state->effective($set, $super) : [];
            $new = $eligibility?->mayOperate() ? $this->state->effective($after, $super) : [];
            $revision = (int) ($c->table('runa_permissions.user_access')->where('user_public_id', $user)->value('version') ?? 0);
            $users[] = ['user_public_id' => $user, 'revision' => $revision, 'before' => $set->toArray(), 'after' => $after->toArray(), 'permission_removed' => array_values(array_diff($old, $new))];
            $binding[] = [$member->id, $eligibility, $this->state->references($set), $old, $new];
        }
        $presets = $references->map(fn ($row) => ['public_id' => $row->public_id, 'version' => (int) $row->version])->all();
        $fingerprint = $this->state->fingerprint([$before, $users, $binding, $references->all()]);

        return new RoleDeletionPreview($publicId, $version, $fingerprint, $users, $presets);
    }

    public function delete(RoleDeletionPreview $reviewed, bool $confirmed): void
    {
        $this->transactions->run(function (Connection $c) use ($reviewed, $confirmed): void {
            if (! $confirmed) {
                $this->state->reject('access_delete_confirmation_required', 409);
            }
            $current = $this->preview($reviewed->publicId, $reviewed->version);
            if (! hash_equals($current->fingerprint, $reviewed->fingerprint)) {
                $this->state->reject('access_preview_stale', 409);
            }
            $before = $this->state->definition('role', $current->publicId);
            $id = $c->table('runa_permissions.roles')->where('public_id', $current->publicId)->value('id');
            foreach ($current->users as $user) {
                $c->table('runa_permissions.user_access')->updateOrInsert(['user_public_id' => $user['user_public_id']], ['version' => $user['revision'] + 1, 'updated_at' => now('UTC')]);
                $this->transactions->record('assignments.changed', 'user', $user['user_public_id'], $user['before'], $user['after'], ['removed_role' => $current->publicId, 'revision' => $user['revision'] + 1]);
            }
            foreach ($current->presets as $preset) {
                $old = $this->state->definition('preset', $preset['public_id']);
                $presetId = $c->table('runa_permissions.presets')->where('public_id', $preset['public_id'])->value('id');
                $c->table('runa_permissions.preset_roles')->where('preset_id', $presetId)->where('role_id', $id)->delete();
                $c->table('runa_permissions.presets')->where('id', $presetId)->update(['version' => $preset['version'] + 1, 'updated_at' => now('UTC')]);
                $this->transactions->record('preset.saved', 'preset', $preset['public_id'], $old, $this->state->definition('preset', $preset['public_id']), ['removed_role' => $current->publicId]);
            }
            $c->table('runa_permissions.user_roles')->where('role_id', $id)->delete();
            $c->table('runa_permissions.role_permissions')->where('role_id', $id)->delete();
            $c->table('runa_permissions.roles')->where('id', $id)->update(['version' => $current->version + 1, 'deleted_at' => now('UTC'), 'updated_at' => now('UTC')]);
            $this->transactions->record('role.force_deleted', 'role', $current->publicId, $before, null,
                ['affected_users' => count($current->users), 'affected_presets' => count($current->presets)]);
        }, write: true, permission: 'runa.permissions.roles.force_delete');
    }
}
