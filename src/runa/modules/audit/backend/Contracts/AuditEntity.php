<?php

namespace Runa\Modules\Audit\Contracts;

use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\Support\Modules\ModuleIdentity;

final readonly class AuditEntity
{
    public function __construct(public string $module, public string $type, public string $publicId)
    {
        new ModuleIdentity($module);
        new TrustedPrincipal($publicId);
        if (preg_match('/\A[a-z][a-z0-9_.]{0,63}\z/D', $type) !== 1) {
            throw new \InvalidArgumentException('Invalid Audit entity type.');
        }
    }
}
