<?php

namespace Runa\Contracts\Modules;

use InvalidArgumentException;

final readonly class JobDefinition
{
    public function __construct(public string $key, public string $handler, public string $policy, public int $maxAttempts, public int $retrySeconds, public int $leaseSeconds, public bool $cancellable, public string $limitations, public ?string $permission = null, public int $schemaVersion = 1)
    {
        new ApiOperation($key, $permission);
        if (preg_match('/\A[a-z][a-z0-9_]{0,63}\z/D', $limitations) !== 1 || $schemaVersion !== 1 || $maxAttempts < 1 || $maxAttempts > 100 || $retrySeconds < 1 || $retrySeconds > 86400 || $leaseSeconds < 10 || $leaseSeconds > 3600) {
            throw new InvalidArgumentException('Invalid module job bounds.');
        }
    }
}
