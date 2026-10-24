<?php

namespace Runa\Modules\Permissions\Contracts;

use Runa\Contracts\Modules\TrustedPrincipal;

final readonly class AuthorityPreview
{
    public function __construct(public string $userPublicId, public bool $grant, public string $fingerprint,
        public bool $current, public int $revision, public bool $eligible)
    {
        new TrustedPrincipal($userPublicId);
    }
}
