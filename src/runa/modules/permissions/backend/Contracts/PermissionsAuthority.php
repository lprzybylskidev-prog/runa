<?php

namespace Runa\Modules\Permissions\Contracts;

/** Dedicated protected-authority and destructive custom-role contracts. */
interface PermissionsAuthority
{
    public function previewAuthority(string $userPublicId, bool $grant): AuthorityPreview;

    /** @return array{user_public_id: string, super_admin: bool, revision: int} */
    public function applyAuthority(AuthorityPreview $reviewed): array;

    public function previewRoleDeletion(string $publicId, int $version): RoleDeletionPreview;

    public function forceDeleteRole(RoleDeletionPreview $reviewed, bool $confirmed): void;
}
