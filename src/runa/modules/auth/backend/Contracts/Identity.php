<?php

namespace Runa\Modules\Auth\Contracts;

use Runa\Contracts\Modules\TrustedPrincipal;

/** Safe historical display, without email, credentials or internal keys. */
final readonly class Identity
{
    public function __construct(public string $publicId, public string $displayName, public bool $deleted, public int $version)
    {
        new TrustedPrincipal($publicId);
        if (trim($displayName) === '' || mb_strlen($displayName) > 321 || $version < 1) {
            throw new \InvalidArgumentException('Invalid identity projection.');
        }
    }
}
