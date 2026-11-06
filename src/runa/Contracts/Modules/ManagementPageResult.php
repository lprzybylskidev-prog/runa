<?php

namespace Runa\Contracts\Modules;

use Runa\ModuleFramework\Management\InputValues as Values;

final readonly class ManagementPageResult
{
    /** @param list<ManagementRecord> $items */
    public function __construct(
        public array $items, public ?string $next, public ?int $total = null,
    ) {
        if (count($items) > 100 || ($next !== null && strlen($next) > 2048) || ($total !== null && $total < 0)) {
            throw new \InvalidArgumentException('Invalid management page result.');
        }
        Values::objects($items, ManagementRecord::class, 100);
    }
}
