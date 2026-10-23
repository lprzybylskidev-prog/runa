<?php

namespace Runa\Contracts\Modules;

interface SettingsProvider
{
    /** @return list<SettingDefinition> */
    public function settings(): array;

    /** Backend cross-field validation only. Never retain/log candidate values or throw value-bearing errors.
     * @param  array<string, mixed>  $values
     */
    public function valid(array $values): bool;

    /** Resolve trusted module-owned handler through the declaration boundary. */
    public function handler(): string;
}
