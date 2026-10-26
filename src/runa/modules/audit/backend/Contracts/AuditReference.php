<?php

namespace Runa\Modules\Audit\Contracts;

final readonly class AuditReference
{
    public function __construct(public string $publicId, public string $occurredAt) {}
}
