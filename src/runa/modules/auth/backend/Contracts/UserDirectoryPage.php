<?php

namespace Runa\Modules\Auth\Contracts;

use Runa\ModuleFramework\Management\InputValues as Values;

final readonly class UserDirectoryPage
{
    /** @param list<AdministrativeUser> $items */
    public function __construct(public array $items, public ?string $next, public ?int $total = null)
    {
        if (count($items) > 100 || ($total !== null && $total < 0) || ($next !== null && strlen($next) > 2048)) {
            throw new \InvalidArgumentException('Invalid user directory page.');
        }
        Values::objects($items, AdministrativeUser::class, 100);

    }
}
