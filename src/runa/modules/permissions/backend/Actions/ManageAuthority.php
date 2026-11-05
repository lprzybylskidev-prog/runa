<?php

namespace Runa\Modules\Permissions\Actions;

use Illuminate\Database\Connection;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\ModuleFramework\Api\PrincipalScope;
use Runa\Modules\Auth\Contracts\AuthorizationIdentity;
use Runa\Modules\Permissions\Contracts\AuthorityBatchPreview;
use Runa\Modules\Permissions\Contracts\AuthorityPreview;
use Runa\Modules\Permissions\Contracts\PermissionsAuthority;
use Runa\Modules\Permissions\Contracts\PermissionsAuthorityBatch;
use Runa\Modules\Permissions\Contracts\PermissionsSelections;
use Runa\Modules\Permissions\Contracts\RoleDeletionPreview;
use Runa\Modules\Permissions\Contracts\UserSelection;
use Runa\Modules\Permissions\Support\AccessState;
use Runa\Modules\Permissions\Support\AccessTransaction;
use Runa\Modules\Permissions\Support\PreserveAuthority;
use Runa\Support\Modules\Api\ModuleApis;

final readonly class ManageAuthority implements PermissionsAuthority, PermissionsAuthorityBatch
{
    public function __construct(private AccessState $state,
        private AccessTransaction $transactions, private PreserveAuthority $authority,
        private AuthorizationIdentity $identities, private PrincipalScope $principals, private DeleteRoles $roles, private ModuleApis $apis) {}

    public function previewAuthority(string $userPublicId, bool $grant): AuthorityPreview
    {
        return $this->transactions->run(fn (Connection $c) => $this->preview($c, $userPublicId, $grant), permission: 'runa.permissions.authority.manage');
    }

    /**
     * @param  (\stdClass&object{id: int, public_id: string, version: int})|null  $role
     * @param  array{membership: object|null, revision: int}|null  $snapshot
     */
    private function preview(Connection $c, string $userPublicId, bool $grant, ?object $role = null, ?array $snapshot = null): AuthorityPreview
    {
        new TrustedPrincipal($userPublicId);
        $eligibility = $this->identities->eligibility($userPublicId);
        if ($eligibility === null || $eligibility->identity->deleted) {
            $this->state->reject('access_subject_missing', 404);
        }
        $role ??= $this->role($c);
        $membership = $snapshot !== null ? $snapshot['membership'] : $c->table('runa_permissions.user_roles')->where('role_id', $role->id)->where('user_public_id', $userPublicId)->first();
        $current = $membership !== null;
        if ($grant && ! $current && $this->principals->current()?->publicId === $userPublicId) {
            $this->state->reject('authority_self_escalation', 403);
        }
        /** @var int|null $storedRevision */
        $storedRevision = $snapshot !== null ? $snapshot['revision'] : $c->table('runa_permissions.user_access')->where('user_public_id', $userPublicId)->value('version');
        $revision = $storedRevision ?? 0;
        $fingerprint = $this->state->fingerprint([$userPublicId, $grant, $eligibility, $role->public_id, $role->version, $membership?->id, $revision]);

        return new AuthorityPreview($userPublicId, $grant, $fingerprint, $current, $revision, $eligibility->mayOperate());
    }

    public function applyAuthority(AuthorityPreview $reviewed): array
    {
        return $this->transactions->run(function (Connection $c) use ($reviewed): array {
            $current = $this->preview($c, $reviewed->userPublicId, $reviewed->grant);
            if (! hash_equals($current->fingerprint, $reviewed->fingerprint)) {
                $this->state->reject('access_preview_stale', 409);
            }
            if ($current->current === $current->grant) {
                return ['user_public_id' => $current->userPublicId, 'super_admin' => $current->current, 'revision' => $current->revision];
            }
            if (! $current->grant) {
                $this->authority->assertMayRemove($c, [$current->userPublicId]);
            }

            return $this->applyValidated($c, $current, $this->role($c));
        }, write: true, permission: 'runa.permissions.authority.manage');
    }

    /**
     * @param  \stdClass&object{id: int, public_id: string, version: int}  $role
     * @return array{user_public_id: string, super_admin: bool, revision: int}
     */
    private function applyValidated(Connection $c, AuthorityPreview $current, object $role): array
    {
        if ($current->current === $current->grant) {
            return ['user_public_id' => $current->userPublicId, 'super_admin' => $current->current, 'revision' => $current->revision];
        }
        if ($current->grant) {
            $c->table('runa_permissions.user_roles')->insert(['role_id' => $role->id, 'user_public_id' => $current->userPublicId,
                'created_at' => now('UTC'), 'updated_at' => now('UTC')]);
        } else {
            $c->table('runa_permissions.user_roles')->where('role_id', $role->id)->where('user_public_id', $current->userPublicId)->delete();
        }
        $revision = $current->revision + 1;
        $c->table('runa_permissions.user_access')->updateOrInsert(['user_public_id' => $current->userPublicId], ['version' => $revision, 'updated_at' => now('UTC')]);
        $this->transactions->record($current->grant ? 'authority.granted' : 'authority.removed', 'user', $current->userPublicId,
            ['super_admin' => $current->current, 'revision' => $current->revision], ['super_admin' => $current->grant, 'revision' => $revision], ['role' => $role->public_id]);

        return ['user_public_id' => $current->userPublicId, 'super_admin' => $current->grant, 'revision' => $revision];
    }

    public function previewBatch(UserSelection $selection, bool $grant): AuthorityBatchPreview
    {
        return $this->transactions->run(fn (Connection $c) => $this->batch($c, $selection, $grant), permission: 'runa.permissions.authority.manage');
    }

    private function batch(Connection $c, UserSelection $selection, bool $grant): AuthorityBatchPreview
    {
        if ($selection->all) {
            $this->state->reject('access_selection_invalid', 422);
        }
        $selection = $this->apis->resolve('runa.permissions', PermissionsSelections::class, '1.0.0')->users($selection);
        $role = $this->role($c);
        $members = $c->table('runa_permissions.user_roles')->where('role_id', $role->id)->whereIn('user_public_id', $selection->publicIds)->get()->keyBy('user_public_id');
        $revisions = $c->table('runa_permissions.user_access')->whereIn('user_public_id', $selection->publicIds)->pluck('version', 'user_public_id');
        $targets = array_map(fn (string $id): AuthorityPreview => $this->preview($c, $id, $grant, $role, ['membership' => $members->get($id), 'revision' => $revisions->get($id, 0)]), $selection->publicIds);
        $skipped = $grant ? [] : $this->authority->retainedForRemoval($c, array_values(array_map(fn (AuthorityPreview $target): string => $target->userPublicId, array_filter($targets, fn (AuthorityPreview $target): bool => $target->current))));

        return new AuthorityBatchPreview($selection->publicIds, $grant, $this->state->fingerprint(['authority_batch', $selection->publicIds, $grant, array_map(fn (AuthorityPreview $target): string => $target->fingerprint, $targets), $skipped]), $targets, $skipped);
    }

    public function applyBatch(AuthorityBatchPreview $reviewed): array
    {
        return $this->transactions->run(function (Connection $c) use ($reviewed): array {
            $current = $this->batch($c, new UserSelection($reviewed->publicIds), $reviewed->grant);
            if (! hash_equals($current->fingerprint, $reviewed->fingerprint)) {
                $this->state->reject('access_preview_stale', 409);
            }
            $role = $this->role($c);

            $removals = array_values(array_diff(array_map(fn (AuthorityPreview $target): string => $target->userPublicId, array_filter($current->targets, fn (AuthorityPreview $target): bool => $target->current)), $current->skippedPublicIds));
            if (! $current->grant) {
                $this->authority->assertMayRemove($c, $removals);
            }

            return array_map(fn (AuthorityPreview $target): array => in_array($target->userPublicId, $current->skippedPublicIds, true)
                ? ['user_public_id' => $target->userPublicId, 'super_admin' => $target->current, 'revision' => $target->revision]
                : $this->applyValidated($c, $target, $role), $current->targets);
        }, write: true, permission: 'runa.permissions.authority.manage');
    }

    public function previewRoleDeletion(string $publicId, int $version): RoleDeletionPreview
    {
        return $this->transactions->run(fn () => $this->roles->preview($publicId, $version), permission: 'runa.permissions.roles.force_delete');
    }

    public function forceDeleteRole(RoleDeletionPreview $reviewed, bool $confirmed): void
    {
        $this->roles->delete($reviewed, $confirmed);
    }

    /** @return \stdClass&object{id: int, public_id: string, version: int} */
    private function role(Connection $c): object
    {
        /** @var \stdClass&object{id: int, public_id: string, version: int} $role */
        $role = $c->table('runa_permissions.roles')->where('code', 'runa.permissions.super_admin')->where('system', true)->whereNull('deleted_at')->first()
            ?? throw new \RuntimeException('Protected system role unavailable.');

        return $role;
    }
}
