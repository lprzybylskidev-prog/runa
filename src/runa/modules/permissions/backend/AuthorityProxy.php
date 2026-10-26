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
        return $this->invoke(__FUNCTION__, [$userPublicId, $grant]);
    }

    public function applyAuthority(AuthorityPreview $reviewed): array
    {
        return $this->invoke(__FUNCTION__, [$reviewed]);
    }

    public function previewRoleDeletion(string $publicId, int $version): RoleDeletionPreview
    {
        return $this->invoke(__FUNCTION__, [$publicId, $version]);
    }

    public function forceDeleteRole(RoleDeletionPreview $reviewed, bool $confirmed): void
    {
        $this->invoke(__FUNCTION__, [$reviewed, $confirmed]);
    }
}
