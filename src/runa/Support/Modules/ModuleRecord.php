<?php

namespace Runa\Support\Modules;

/** Administrative/source state only; availability and installed-data checks are separate. */
final readonly class ModuleRecord
{
    public function __construct(
        public string $publicId,
        public string $id,
        public string $kind,
        public bool $registered,
        public bool $sourcePresent,
        public bool $enabled,
        public string $sourceVersion,
        public ?string $installedVersion,
        public string $sourcePath,
        public string $manifestHash,
    ) {}
}
