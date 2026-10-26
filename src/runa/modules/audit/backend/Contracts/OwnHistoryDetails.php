<?php

namespace Runa\Modules\Audit\Contracts;

/** Explicit source-owned field projection, always bound to the current human entity. */
interface OwnHistoryDetails
{
    /** @param array<string, list<string>> $fieldsByAction */
    public function detail(string $module, string $entityType, string $publicId, array $fieldsByAction): ?array;
}
