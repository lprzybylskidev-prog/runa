<?php

namespace Runa\Support\Modules;

final readonly class RegistrySnapshot
{
    /** @param array<string, ModuleRecord> $modules */
    public function __construct(public int $generation, public array $modules) {}
}
