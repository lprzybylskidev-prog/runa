<?php

namespace Runa\Modules\Auth\Contracts;

use Runa\Contracts\Modules\TrustedPrincipal;

final readonly class SessionSelection
{
    /** Bulk execution is atomic and capped at 100 accounts, including all-user mode. */
    public function __construct(public array $publicIds = [], public bool $all = false)
    {
        if (($all && $publicIds !== []) || (! $all && ($publicIds === [] || count($publicIds) > 100 || ! array_is_list($publicIds)))
            || count(array_unique($publicIds)) !== count($publicIds)) {
            throw new \InvalidArgumentException('Invalid bounded session selection.');
        }
        foreach ($publicIds as $id) {
            new TrustedPrincipal($id);
        }
    }
}
