<?php

namespace Runa\Modules\Auth\Contracts;

use Runa\Contracts\Modules\TrustedPrincipal;

final readonly class AdministrativeUser
{
    /** @param array<string,bool|int|string|null> $details Authorized safe account metadata. */
    public function __construct(public string $publicId, public string $displayName, public string $state, public int $version, public array $details = [])
    {
        new TrustedPrincipal($publicId);
        if (trim($displayName) === '' || mb_strlen($displayName) > 321 || $version < 1 || ! in_array($state, ['current', 'deleted'], true)) {
            throw new \InvalidArgumentException('Invalid administrative user.');
        }
    }
}
