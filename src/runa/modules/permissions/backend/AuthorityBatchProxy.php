<?php

namespace Runa\Modules\Permissions;

use Runa\Modules\Permissions\Contracts\AuthorityBatchPreview;
use Runa\Modules\Permissions\Contracts\PermissionsAuthorityBatch;
use Runa\Modules\Permissions\Contracts\UserSelection;
use Runa\Support\Modules\Api\GuardedApi;

final class AuthorityBatchProxy extends GuardedApi implements PermissionsAuthorityBatch
{
    public function previewBatch(UserSelection $selection, bool $grant): AuthorityBatchPreview
    {
        /** @var AuthorityBatchPreview $result */
        $result = $this->invoke(__FUNCTION__, [$selection, $grant]);

        return $result;
    }

    public function applyBatch(AuthorityBatchPreview $reviewed): array
    {
        /** @var list<array{user_public_id:string,super_admin:bool,revision:int}> $result */
        $result = $this->invoke(__FUNCTION__, [$reviewed]);

        return $result;
    }
}
