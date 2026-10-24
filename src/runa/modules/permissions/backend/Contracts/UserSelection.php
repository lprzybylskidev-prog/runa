<?php

namespace Runa\Modules\Permissions\Contracts;

use Runa\Contracts\Modules\TrustedPrincipal;

/** Explicit bounded selection intent. All mode is resolved atomically by the owning module. */
final readonly class UserSelection
{
    public function __construct(public array $publicIds = [], public bool $all = false)
    {
        if (($all && $publicIds !== []) || (! $all && ($publicIds === [] || count($publicIds) > 100 || ! array_is_list($publicIds)))
            || count(array_unique($publicIds)) !== count($publicIds)) {
            throw new \InvalidArgumentException('Invalid user selection.');
        }
        foreach ($publicIds as $id) {
            new TrustedPrincipal($id);
        }
    }
}
