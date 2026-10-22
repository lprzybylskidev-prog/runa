<?php

namespace Runa\Contracts\Modules;

use InvalidArgumentException;
use Runa\Support\Modules\ModuleIdentity;

final readonly class ListenerDefinition
{
    public function __construct(public string $key, public string $publisher, public string $event, public int $version, public string $mode, public string $target, public ?string $integration = null, public int $schemaVersion = 1)
    {
        new EventDefinition($event, $version, $schemaVersion);
        new ApiOperation($key);
        new ModuleIdentity($publisher);
        if ($integration !== null) {
            new ApiOperation($integration);
        }
        if (preg_match('/\A[a-z][a-z0-9_]{0,63}\z/D', $key) !== 1 || ! in_array($mode, ['sync', 'async'], true) || $target === '') {
            throw new InvalidArgumentException('Invalid listener mode or target.');
        }
        if ($mode === 'async') {
            new ApiOperation($target);
        }
    }
}
