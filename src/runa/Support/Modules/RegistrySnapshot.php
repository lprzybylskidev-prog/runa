<?php

namespace Runa\Support\Modules;

final readonly class RegistrySnapshot
{
    /** @param array<string, ModuleRecord> $modules
     * @param  list<string>  $fenced
     */
    public function __construct(public int $generation, public array $modules, public array $fenced = []) {}
}
