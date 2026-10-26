<?php

namespace Runa\Contracts\Modules;

use InvalidArgumentException;

/** Trusted server identity only; never session cookies, credentials or step-up proofs. */
final readonly class TrustedPrincipal
{
    public function __construct(public string $publicId, public string $kind = 'human')
    {
        if (preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/D', $publicId) !== 1
            || ! in_array($kind, ['human', 'machine'], true)) {
            throw new InvalidArgumentException('Invalid trusted principal.');
        }
    }
}
