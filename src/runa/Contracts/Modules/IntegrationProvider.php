<?php

namespace Runa\Contracts\Modules;

interface IntegrationProvider
{
    /** @return list<IntegrationDefinition> */
    public function integrations(): array;

    /** Side-effect-free validation of the complete non-sensitive config.
     * @param  array<string, mixed>  $config
     */
    public function validConfiguration(string $key, array $config): bool;
}
