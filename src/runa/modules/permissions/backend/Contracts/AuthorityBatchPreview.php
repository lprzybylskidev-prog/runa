<?php

namespace Runa\Modules\Permissions\Contracts;

use Runa\ModuleFramework\Management\InputValues as Values;

final readonly class AuthorityBatchPreview
{
    /**
     * @param list<string> $publicIds
     * @param list<AuthorityPreview> $targets
     * @param list<string> $skippedPublicIds */
    public function __construct(public array $publicIds, public bool $grant, public string $fingerprint, public array $targets, public array $skippedPublicIds)
    {
        new UserSelection($publicIds);
        Values::objects($targets, AuthorityPreview::class, 100);
        Values::strings($skippedPublicIds);
        if (count($targets) !== count($publicIds)) {
            throw new \InvalidArgumentException('Invalid authority batch snapshot.');
        }
        if (count(array_unique($skippedPublicIds)) !== count($skippedPublicIds) || array_diff($skippedPublicIds, $publicIds) !== [] || ($grant && $skippedPublicIds !== [])) {
            throw new \InvalidArgumentException('Invalid retained authority selection.');
        }
        foreach ($targets as $index => $target) {
            if ($target->userPublicId !== $publicIds[$index] || $target->grant !== $grant || (in_array($target->userPublicId, $skippedPublicIds, true) && ! $target->current)) {
                throw new \InvalidArgumentException('Invalid authority batch target.');
            }
        }
    }
}
