<?php

namespace Runa\Contracts\Modules;

final readonly class ManagementPageResult
{
    /** @param list<ManagementRecord> $items */
    public function __construct(
        public array $items, public ?string $next, public ?int $total = null,
    ) {
        if (! array_is_list($items) || count($items) > 100 || ($next !== null && strlen($next) > 2048) || ($total !== null && $total < 0)) {
            throw new \InvalidArgumentException('Invalid management page result.');
        }
        foreach ($items as $item) {
            if (! $item instanceof ManagementRecord) {
                throw new \InvalidArgumentException('Invalid management record.');
            }
        }
    }
}
