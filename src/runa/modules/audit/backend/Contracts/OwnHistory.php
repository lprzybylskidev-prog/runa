<?php

namespace Runa\Modules\Audit\Contracts;

/** Safe summaries bound to the trusted human's UUIDv7 entity, never arbitrary subjects. */
interface OwnHistory
{
    /** @param list<string> $actions */
    public function page(string $module, string $entityType, array $actions, ?string $before = null): array;
}
