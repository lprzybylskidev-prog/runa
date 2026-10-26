<?php

namespace Runa\Contracts\Modules;

use Illuminate\Database\Connection;

/** Exact reserved transactional step; a PHP capability, never an API payload. */
final readonly class PreparationContext
{
    public function __construct(
        public Connection $connection, public string $owner, public string $operation,
        public string $module, public string $step, public string $fingerprint,
        public string $manifestHash, public string $reservationFingerprint,
    ) {}
}
