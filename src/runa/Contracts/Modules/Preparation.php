<?php

namespace Runa\Contracts\Modules;

use InvalidArgumentException;

/** Tokens are private, bounded and encrypted at rest; never include them in management diagnostics. */
final readonly class Preparation
{
    public function __construct(public string $state, public ?string $token = null, public string $code = 'ready')
    {
        if (! in_array($state, ['ready', 'waiting', 'refused'], true) || ($token !== null && strlen($token) > 8192) || preg_match('/\A[a-z][a-z0-9_]{0,63}\z/D', $code) !== 1) {
            throw new InvalidArgumentException('Invalid preparation result.');
        }
    }
}
