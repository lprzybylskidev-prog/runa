<?php

namespace Runa\Modules\Permissions;

use Runa\Modules\Permissions\Contracts\AuthorityPreview;
use Runa\Modules\Permissions\Contracts\PermissionsAuthority;
use Runa\Modules\Permissions\Contracts\RoleDeletionPreview;
use Runa\Support\Modules\Api\GuardedApi;

final class AuthorityProxy extends GuardedApi implements PermissionsAuthority
{
    public function previewAuthority(string $userPublicId, bool $grant): AuthorityPreview
    {
        /** @var AuthorityPreview $result */
        $result = $this->invoke(__FUNCTION__, [$userPublicId, $grant]);

        return $result;
    }

    public function applyAuthority(AuthorityPreview $reviewed): array
    {
        /** @var array{user_public_id: string, super_admin: bool, revision: int} $result */
        $result = $this->invoke(__FUNCTION__, [$reviewed]);

        return $result;
    }

    public function previewRoleDeletion(string $publicId, int $version): RoleDeletionPreview
    {
        /** @var RoleDeletionPreview $result */
        $result = $this->invoke(__FUNCTION__, [$publicId, $version]);

        return $result;
    }

    public function forceDeleteRole(RoleDeletionPreview $reviewed, bool $confirmed): void
    {
        $this->invoke(__FUNCTION__, [$reviewed, $confirmed]);
    }
}
