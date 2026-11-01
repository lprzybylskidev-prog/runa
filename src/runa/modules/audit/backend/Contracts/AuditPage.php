<?php

namespace Runa\Modules\Audit\Contracts;

final readonly class AuditPage
{
    /** @param list<AuditEntry> $entries */
    public function __construct(public array $entries, public ?string $nextBefore, public ?int $total = null)
    {
        if ($total !== null && $total < 0) {
            throw new \InvalidArgumentException('Invalid Audit page total.');
        }
    }
}
