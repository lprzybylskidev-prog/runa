<?php

namespace Runa\Modules\Auth\Contracts;

use Runa\Contracts\Modules\TrustedPrincipal;

final readonly class SessionSelection
{
    /** @var list<string> */
    public array $publicIds;

    /** Bulk execution is atomic and capped at 100 accounts, including all-user mode.
     *
     * @param  array<array-key, mixed>  $publicIds
     */
    public function __construct(array $publicIds = [], public bool $all = false)
    {
        if (($all && $publicIds !== []) || (! $all && ($publicIds === [] || count($publicIds) > 100 || ! array_is_list($publicIds)))) {
            throw new \InvalidArgumentException('Invalid bounded session selection.');
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
