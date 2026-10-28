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
        /** @var AuditPage $result */
        $result = $this->invoke(__FUNCTION__, [$query]);

        return $result;
    }

    public function detail(string $publicId): ?AuditEntry
    {
        /** @var ?AuditEntry $result */
        $result = $this->invoke(__FUNCTION__, [$publicId]);

        return $result;
    }

    public function metadata(): array
    {
        /** @var array{version: int, order: string, max_page_size: int, max_page_bytes: int, payloads: string, filters: list<string>} $result */
        $result = $this->invoke(__FUNCTION__, []);

        return $result;
    }
}
