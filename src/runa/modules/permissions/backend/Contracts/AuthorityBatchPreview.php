<?php

namespace Runa\Modules\Permissions\Contracts;

final readonly class AuthorityBatchPreview
{
    /** @param list<string> $publicIds
     * @param list<AuthorityPreview> $targets */
    public function __construct(public array $publicIds, public bool $grant, public string $fingerprint, public array $targets)
    {
        new UserSelection($publicIds);
        if (! array_is_list($targets) || count($targets) !== count($publicIds)) {
            throw new \InvalidArgumentException('Invalid authority batch snapshot.');
        }
        foreach ($targets as $index => $target) {
            if (! $target instanceof AuthorityPreview || $target->userPublicId !== $publicIds[$index] || $target->grant !== $grant) {
                throw new \InvalidArgumentException('Invalid authority batch target.');
            }
        }
    }
}
