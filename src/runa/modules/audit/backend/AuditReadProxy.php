<?php

namespace Runa\Modules\Audit;

use Runa\Modules\Audit\Contracts\AuditEntry;
use Runa\Modules\Audit\Contracts\AuditPage;
use Runa\Modules\Audit\Contracts\AuditQuery;
use Runa\Modules\Audit\Contracts\AuditReads;
use Runa\Support\Modules\Api\GuardedApi;

final class AuditReadProxy extends GuardedApi implements AuditReads
{
    public function page(AuditQuery $query): AuditPage
    {
        return $this->invoke(__FUNCTION__, [$query]);
    }

    public function detail(string $publicId): ?AuditEntry
    {
        return $this->invoke(__FUNCTION__, [$publicId]);
    }

    public function metadata(): array
    {
        return $this->invoke(__FUNCTION__, []);
    }
}
