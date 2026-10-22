<?php

namespace Runa\Contracts\Modules;

use InvalidArgumentException;
use Runa\Support\VersionCompatibility;

final readonly class UpgradeDefinition
{
    /** @param list<string> $requires */
    public function __construct(
        public string $key,
        public ?string $from,
        public string $to,
        public string $handler,
        public bool $transactional = true,
        public bool $idempotent = false,
        public string $recovery = 'transaction_rollback',
        public array $requires = [],
        public int $schemaVersion = 1,
    ) {
        new ApiOperation($key);
        new ApiOperation($recovery);
        if ($schemaVersion !== 1 || ! (new VersionCompatibility)->supports($to, $to) || ($from !== null && (! (new VersionCompatibility)->supports($from, $from) || version_compare($to, $from, '<=')))) {
            throw new InvalidArgumentException('Invalid forward module upgrade versions.');
        }
        if (! $transactional && $recovery === 'transaction_rollback') {
            throw new InvalidArgumentException('Nontransactional upgrades require a documented operator recovery key.');
        }
        foreach ($requires as $key) {
            new ApiOperation($key);
        }
        if (count($requires) > 256 || count(array_unique($requires)) !== count($requires)) {
            throw new InvalidArgumentException('Invalid module upgrade prerequisites.');
        }
    }
}
