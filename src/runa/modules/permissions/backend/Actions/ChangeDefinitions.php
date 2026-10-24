<?php

namespace Runa\Modules\Permissions\Actions;

use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Runa\Modules\Permissions\Contracts\AssignmentSet;
use Runa\Modules\Permissions\Contracts\DefinitionInput;
use Runa\Modules\Permissions\Contracts\DefinitionPreview;
use Runa\Modules\Permissions\Support\AccessState;
use Runa\Modules\Permissions\Support\AccessTransaction;

/** Custom dictionaries only; source/system dictionaries have no administrative writer. */
final readonly class ChangeDefinitions
{
    public function __construct(private DatabaseManager $database, private AccessState $state, private AccessTransaction $transactions) {}

    public function preview(DefinitionInput $input): DefinitionPreview
    {
        $c = $this->database->connection();
        $table = $this->state->table($input->kind);
        $before = $input->publicId === null ? null : $this->state->definition($input->kind, $input->publicId);
        if ($before !== null && ($before['module'] !== null || $before['system'])) {
            $this->state->reject('access_definition_protected', 403);
        }
        if ($before !== null && ($before['version'] !== $input->version || $before['dictionary']['code'] !== $input->dictionary['code'])) {
            $this->state->reject('access_definition_stale', 409);
        }
        if ($before === null && $c->table('runa_permissions.'.$table)->where('code', $input->dictionary['code'])->exists()) {
            $this->state->reject('access_definition_conflict', 409);
        }
        $source = $input->sourceUser === null ? null : $this->state->user($input->sourceUser);
        $set = $source === null ? $input->assignments : new AssignmentSet(...$source['assigned']);
        $references = $this->state->references($set);
        $oldSet = $before === null ? new AssignmentSet : new AssignmentSet(...$before['assigned']);
        $oldEffective = $this->state->effective($oldSet);
        $newEffective = $this->state->effective($set);
        $users = $input->kind !== 'role' || $before === null ? null : $c->table('runa_permissions.user_roles as ur')
            ->join('runa_permissions.roles as r', 'r.id', '=', 'ur.role_id')->where('r.public_id', $input->publicId)->selectRaw("count(*) as total, md5(coalesce(string_agg(ur.user_public_id::text || ':' || ur.id::text, ',' ORDER BY ur.user_public_id), '')) as signature")->first();
        $after = ['dictionary' => $input->dictionary, 'assigned' => $set->toArray()];
        $fingerprint = $this->state->fingerprint([$input, $source, $before, $after, $references, (array) $users, $oldEffective, $newEffective]);

        return new DefinitionPreview($input, $fingerprint, $before, $after, (int) ($users->total ?? 0),
            array_values(array_diff($newEffective, $oldEffective)), array_values(array_diff($oldEffective, $newEffective)));
    }

    public function save(DefinitionPreview $reviewed): array
    {
        return $this->transactions->run(function (Connection $c) use ($reviewed): array {
            $current = $this->preview($reviewed->input);
            if (! hash_equals($reviewed->fingerprint, $current->fingerprint)) {
                $this->state->reject('access_preview_stale', 409);
            }
            $input = $current->input;
            $table = $this->state->table($input->kind);
            $publicId = $input->publicId ?? (string) Str::uuid7();
            if ($current->before !== null && $current->after === array_intersect_key($current->before, $current->after)) {
                return $current->before;
            }
            $data = $current->after['dictionary'] + ['updated_at' => now('UTC')];
            if ($current->before === null) {
                $id = $c->table('runa_permissions.'.$table)->insertGetId($data + ['public_id' => $publicId, 'created_at' => now('UTC')]);
            } else {
                $id = $c->table('runa_permissions.'.$table)->where('public_id', $publicId)->value('id');
                $c->table('runa_permissions.'.$table)->where('id', $id)->update($data + ['version' => $input->version + 1]);
            }
            $this->writeMembers($c, $input->kind, $id, new AssignmentSet(...$current->after['assigned']));
            $after = $this->state->definition($input->kind, $publicId);
            $this->transactions->record($input->kind.'.saved', $input->kind, $publicId, $current->before, $after,
                ['affected_users' => $current->affectedUsers, 'source_user' => $input->sourceUser]);

            return $after;
        }, write: true);
    }

    public function delete(string $kind, string $publicId, int $version): void
    {
        $this->transactions->run(function (Connection $c) use ($kind, $publicId, $version): void {
            if (! in_array($kind, ['role', 'preset'], true)) {
                $this->state->reject('access_definition_protected', 403);
            }
            $table = $this->state->table($kind);
            $before = $this->state->definition($kind, $publicId);
            if ($before['module'] !== null || $before['system']) {
                $this->state->reject('access_definition_protected', 403);
            }
            if ($before['version'] !== $version) {
                $this->state->reject('access_definition_stale', 409);
            }
            $id = $c->table('runa_permissions.'.$table)->where('public_id', $publicId)->value('id');
            if ($kind === 'role' && ($c->table('runa_permissions.user_roles')->where('role_id', $id)->exists()
                || $c->table('runa_permissions.preset_roles')->where('role_id', $id)->exists())) {
                $this->state->reject('access_role_referenced', 409);
            }
            // Preset deletion removes its own links, never recipient assignments.
            $this->writeMembers($c, $kind, $id, new AssignmentSet);
            $c->table('runa_permissions.'.$table)->where('id', $id)->update(['deleted_at' => now('UTC'), 'updated_at' => now('UTC'), 'version' => $version + 1]);
            $this->transactions->record($kind.'.deleted', $kind, $publicId, $before, null);
        }, write: true);
    }

    private function writeMembers(Connection $c, string $kind, int $id, AssignmentSet $set): void
    {
        foreach (['roles' => $set->roles, 'permissions' => $set->permissions] as $members => $ids) {
            if ($kind === 'role' && $members === 'roles') {
                continue;
            }
            $table = 'runa_permissions.'.$kind.'_'.$members;
            $memberKey = $members === 'roles' ? 'role_id' : 'permission_id';
            $c->table($table)->where($kind.'_id', $id)->delete();
            foreach ($c->table('runa_permissions.'.$members)->whereIn('public_id', $ids)->pluck('id') as $member) {
                $c->table($table)->insert([$kind.'_id' => $id, $memberKey => $member, 'created_at' => now('UTC'), 'updated_at' => now('UTC')]);
            }
        }
    }
}
