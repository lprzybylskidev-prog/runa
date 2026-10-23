<?php

namespace Runa\Support\Modules\Management;

use Runa\Contracts\Modules\ApiOperation;
use RuntimeException;

/** Descriptor compatibility catalogue, not an installed Admin UI. Trusted bootstrap only. */
final class ManagementRenderers
{
    /** @var array<string, int> */
    private array $versions = ['setting:string' => 1, 'setting:integer' => 1, 'setting:decimal' => 1, 'setting:boolean' => 1, 'setting:enum' => 1, 'setting:array' => 1, 'action:action' => 1, 'component:panel' => 1];

    public function register(string $kind, string $key, int $version): void
    {
        new ApiOperation($key);
        $identity = $kind.':'.$key;
        if (! in_array($kind, ['setting', 'action', 'component'], true) || $version < 1 || isset($this->versions[$identity]) || count($this->versions) >= 128) {
            throw new RuntimeException('Invalid or duplicate management renderer.');
        }
        $this->versions[$identity] = $version;
    }

    public function require(string $kind, string $key, int $version): void
    {
        if (($this->versions[$kind.':'.$key] ?? null) !== $version) {
            throw new RuntimeException('Unsupported management renderer.');
        }
    }
}
