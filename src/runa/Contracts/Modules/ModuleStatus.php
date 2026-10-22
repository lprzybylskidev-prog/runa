<?php

namespace Runa\Contracts\Modules;

final readonly class ModuleStatus
{
    public int $schemaVersion;

    /** @param list<GraphBlocker> $reasons */
    public function __construct(public string $moduleId, public ?string $publicId, public int $generation, public ?string $sourceVersion, public ?string $installedVersion, public bool $registered, public bool $enabled, public bool $sourcePresent, public string $ownHealth, public bool $available, public array $reasons)
    {
        $this->schemaVersion = 1;
    }
}
