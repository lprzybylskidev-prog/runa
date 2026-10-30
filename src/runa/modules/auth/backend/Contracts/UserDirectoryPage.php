<?php

namespace Runa\Modules\Auth\Contracts;

final readonly class UserDirectoryPage
{
    /** @param list<AdministrativeUser> $items */
    public function __construct(public array $items, public ?string $next)
    {
        if (! array_is_list($items) || count($items) > 100 || ($next !== null && strlen($next) > 2048)) {
            throw new \InvalidArgumentException('Invalid user directory page.');
        }
        foreach ($items as $item) {
            if (! $item instanceof AdministrativeUser) {
                throw new \InvalidArgumentException('Invalid user directory item.');
            }
        }
    }
}
