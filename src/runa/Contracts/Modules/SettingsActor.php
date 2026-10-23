<?php

namespace Runa\Contracts\Modules;

use InvalidArgumentException;

final readonly class SettingsActor
{
    public function __construct(public string $kind, public string $id)
    {
        if (! in_array($kind, ['actor', 'system'], true) || preg_match('/\A[A-Za-z0-9_.:@-]{1,128}\z/D', $id) !== 1) {
            throw new InvalidArgumentException('Invalid settings actor context.');
        }
    }
}
