<?php

namespace Runa\Contracts\Modules;

use InvalidArgumentException;

final readonly class HealthCheck
{
    /** @param list<string> $operations Empty means every operation. */
    public function __construct(public string $id, public string $severity, public int $timeout, public int $interval, public int $expiry, public array $operations = [])
    {
        if (preg_match('/\A[a-z][a-z0-9_]{0,63}\z/D', $id) !== 1 || ! in_array($severity, ['critical', 'warning'], true) || $timeout < 1 || $timeout > 60 || $interval < 1 || $interval > 86400 || $expiry < $interval || $expiry > 604800 || count($operations) > 128) {
            throw new InvalidArgumentException('Invalid module health check.');
        }
        foreach ($operations as $operation) {
            if (preg_match('/\A[a-z][a-z0-9_.:-]{0,127}\z/D', $operation) !== 1) {
                throw new InvalidArgumentException('Invalid health operation.');
            }
        }
    }

    public function applies(string $operation): bool
    {
        return $this->operations === [] || in_array($operation, $this->operations, true);
    }
}
