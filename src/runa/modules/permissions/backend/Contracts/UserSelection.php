<?php

namespace Runa\Modules\Permissions\Contracts;

use Runa\Contracts\Modules\TrustedPrincipal;

/** Explicit bounded selection intent. All mode is resolved atomically by the owning module. */
final readonly class UserSelection
{
    /** @var list<string> */
    public array $publicIds;

    /**
     * @param  array<array-key, mixed>  $publicIds
     */
    public function __construct(array $publicIds = [], public bool $all = false)
    {
        if (($all && $publicIds !== []) || (! $all && ($publicIds === [] || count($publicIds) > 100 || ! array_is_list($publicIds)))) {
            throw new \InvalidArgumentException('Invalid user selection.');
        }
        $ids = [];
        foreach ($publicIds as $id) {
            if (! is_string($id)) {
                throw new \InvalidArgumentException('Selection references must be strings.');
            }
            new TrustedPrincipal($id);
            $ids[] = $id;
        }
        if (count(array_unique($ids)) !== count($ids)) {
            throw new \InvalidArgumentException('Duplicate selection references.');
        }
        $this->publicIds = $ids;
    }
}
