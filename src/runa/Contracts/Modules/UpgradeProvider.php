<?php

namespace Runa\Contracts\Modules;

/** Pure metadata and preflight: no writes, provider boot, migrations or handler execution. */
interface UpgradeProvider
{
    /** @return list<UpgradeDefinition> */
    public function upgrades(): array;

    /** Null means ready; otherwise return a safe English snake_case blocker code. */
    public function preflight(?string $installed, string $target): ?string;
}
