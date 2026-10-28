<?php

namespace Runa\ModuleFramework;

final readonly class ModuleSource
{
    public function __construct(public ModuleManifest $manifest, public string $path, public string $manifestHash) {}
}
