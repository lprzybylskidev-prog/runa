<?php

namespace Runa\Contracts\Modules;

/** Graph-only preview. Module readiness and operational health are separate gates. */
final readonly class LifecyclePreview
{
    public int $schemaVersion;

    /** @param list<string> $affected
     * @param  array<string, bool>  $changes
     * @param  list<GraphBlocker>  $blockers
     */
    public function __construct(
        public string $intent,
        public string $target,
        public int $generation,
        public array $affected,
        public array $changes,
        public array $blockers,
    ) {
        $this->schemaVersion = 1;
    }

    public function graphReady(): bool
    {
        return $this->blockers === [];
    }
}
