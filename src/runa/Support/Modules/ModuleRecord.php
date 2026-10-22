<?php

namespace Runa\Support\Modules;

/** Administrative/source state only; availability and installed-data checks are separate. */
final readonly class ModuleRecord
{
    /** @param array<string, string> $required
     * @param  array<string, string>  $optional
     * @param  array<string, mixed>  $healthOverrides
     * @param  array<string, array{fingerprint: string, passed: bool, checked_at: int, expires_at: int, code: string}>  $healthObservations
     */
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
        public array $required,
        public array $optional,
        public string $platformConstraint,
        public string $frameworkApiConstraint,
        public array $healthOverrides = [],
        public int $healthRevision = 0,
        public array $healthObservations = [],
    ) {}
}
