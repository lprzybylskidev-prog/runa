<?php

namespace Runa\Contracts\Modules;

/** Source-owned optional cooperation; no runtime configuration overrides. */
interface IntegrationProvider
{
    /** @return list<IntegrationDefinition> */
    public function integrations(): array;

    /** Side-effect-free validation of the complete source-owned non-sensitive config.
     * @param  array<string, mixed>  $config
     */
    public function validConfiguration(string $key, array $config): bool;
}
