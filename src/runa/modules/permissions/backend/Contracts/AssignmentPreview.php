<?php

namespace Runa\Modules\Permissions\Contracts;

/** Execution rebuilds all projections from the request and compares the fingerprint. */
final readonly class AssignmentPreview
{
    public function __construct(
        public string $userPublicId, public AssignmentSet $requested, public string $mode,
        public ?string $sourceUser, public ?string $preset, public string $fingerprint,
        public array $before, public array $after, public array $added, public array $removed,
        public array $effectiveAdded, public array $effectiveRemoved,
    ) {}
}
