<?php

namespace Runa\Modules\Permissions\Contracts;

/** Guarded explicit protected-authority batches; resolve at 1.0.0. */
interface PermissionsAuthorityBatch
{
    public function previewBatch(UserSelection $selection, bool $grant): AuthorityBatchPreview;

    /** @return list<array{user_public_id:string,super_admin:bool,revision:int}> */
    public function applyBatch(AuthorityBatchPreview $reviewed): array;
}
