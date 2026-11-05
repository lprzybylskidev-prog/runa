<?php

namespace Runa\Modules\Permissions\Contracts;

final readonly class AuthorityBatchPreview
{
    /** @param list<string> $publicIds
     * @param list<AuthorityPreview> $targets
     * @param list<string> $skippedPublicIds */
    public function __construct(public array $publicIds, public bool $grant, public string $fingerprint, public array $targets, public array $skippedPublicIds)
    {
        new UserSelection($publicIds);
        if (! array_is_list($targets) || count($targets) !== count($publicIds)) {
            throw new \InvalidArgumentException('Invalid authority batch snapshot.');
        }
        if (! array_is_list($skippedPublicIds) || count(array_unique($skippedPublicIds)) !== count($skippedPublicIds) || array_diff($skippedPublicIds, $publicIds) !== [] || ($grant && $skippedPublicIds !== [])) {
            throw new \InvalidArgumentException('Invalid retained authority selection.');
        }
        foreach ($targets as $index => $target) {
            if (! $target instanceof AuthorityPreview || $target->userPublicId !== $publicIds[$index] || $target->grant !== $grant || (in_array($target->userPublicId, $skippedPublicIds, true) && ! $target->current)) {
                throw new \InvalidArgumentException('Invalid authority batch target.');
            }
        }
    }
}
