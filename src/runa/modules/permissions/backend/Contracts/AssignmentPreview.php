<?php

namespace Runa\Modules\Permissions\Contracts;

/** Execution rebuilds all projections from the request and compares the fingerprint. */
final readonly class AssignmentPreview
{
    /**
     * @param  array{roles: list<string>, permissions: list<string>}  $before
     * @param  array{roles: list<string>, permissions: list<string>}  $after
     * @param  array{roles: list<string>, permissions: list<string>}  $added
     * @param  array{roles: list<string>, permissions: list<string>}  $removed
     * @param  list<string>  $effectiveAdded
     * @param  list<string>  $effectiveRemoved
     */
    public function __construct(
        public string $userPublicId, public AssignmentSet $requested, public string $mode,
        public ?string $sourceUser, public ?string $preset, public string $fingerprint,
        public array $before, public array $after, public array $added, public array $removed,
        public array $effectiveAdded, public array $effectiveRemoved,
    ) {}
}
