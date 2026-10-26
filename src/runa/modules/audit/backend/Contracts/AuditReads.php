<?php

namespace Runa\Modules\Audit\Contracts;

/** Every method requires the trusted runa.audit.read permission. No mutation API. */
interface AuditReads
{
    public function page(AuditQuery $query): AuditPage;

    public function detail(string $publicId): ?AuditEntry;

    public function metadata(): array;
}
