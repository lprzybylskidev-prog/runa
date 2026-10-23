<?php

namespace Runa\Modules\Audit\Contracts;

use Runa\Support\Modules\ModuleIdentity;

/** Read execution and authorization are delivered separately from storage. */
final readonly class AuditQuery
{
    public function __construct(public ?string $module = null, public int $limit = 50)
    {
        if ($module !== null) {
            new ModuleIdentity($module);
        }
        if ($limit < 1 || $limit > 100) {
            throw new \InvalidArgumentException('Invalid Audit page size.');
        }
    }
}
