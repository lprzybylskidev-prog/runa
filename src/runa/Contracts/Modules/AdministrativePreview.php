<?php

namespace Runa\Contracts\Modules;

final readonly class AdministrativePreview
{
    public int $schemaVersion;

    /** @param array<string, LifecyclePlan> $plans
     * @param  array<string, string>  $blockers
     */
    public function __construct(public LifecyclePreview $graph, public array $plans, public array $blockers)
    {
        $this->schemaVersion = 1;
    }

    public function ready(): bool
    {
        return $this->graph->graphReady() && $this->blockers === [];
    }
}
