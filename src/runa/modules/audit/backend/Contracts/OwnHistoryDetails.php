<?php

namespace Runa\Modules\Audit\Contracts;

/** Explicit source-owned field projection, always bound to the current human entity. */
interface OwnHistoryDetails
{
    /** @param array<string, list<string>> $fieldsByAction
     * @return array{public_id: string, occurred_at: string, action: string, outcome: string, changes: list<array{field: string, before: mixed, after: mixed}>}|null
     */
    public function detail(string $module, string $entityType, string $publicId, array $fieldsByAction): ?array;
}
