<?php

namespace Runa\Modules\Audit\Contracts;

/** Every method requires the trusted runa.audit.read permission. No mutation API. */
interface AuditReads
{
    public function page(AuditQuery $query): AuditPage;

    public function detail(string $publicId): ?AuditEntry;

    /**
     * @return array{version: int, order: string, max_page_size: int, max_page_bytes: int, payloads: string, filters: list<string>}
     */
    public function metadata(): array;
}
