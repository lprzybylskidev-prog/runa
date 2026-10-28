<?php

namespace Runa\Modules\Audit\Contracts;

/** Safe summaries bound to the trusted human's UUIDv7 entity, never arbitrary subjects. */
interface OwnHistory
{
    /** @param list<string> $actions
     * @return array{entries: list<array{public_id: string, occurred_at: string, action: string, outcome: string}>, next: string|null}
     */
    public function page(string $module, string $entityType, array $actions, ?string $before = null): array;
}
