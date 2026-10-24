<?php

namespace Runa\Modules\Permissions\Contracts;

use Runa\Contracts\Modules\TrustedPrincipal;

final readonly class RoleDeletionPreview
{
    /** @param list<array> $users @param list<array> $presets */
    public function __construct(public string $publicId, public int $version, public string $fingerprint,
        public array $users, public array $presets)
    {
        new TrustedPrincipal($publicId);
    }
}
