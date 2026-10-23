<?php

namespace Runa\Contracts\Modules;

final readonly class AdministrativePreview
{
    public int $schemaVersion;

    /** @param array<string, LifecyclePlan> $plans
     * @param  array<string, string>  $blockers
     * @param  array<string, array<string, int>>  $activeWork
     */
    public function __construct(public LifecyclePreview $graph, public array $plans, public array $blockers, public array $activeWork = [])
    {
        $this->schemaVersion = 1;
    }

    public function ready(): bool
    {
        return $this->graph->graphReady() && $this->blockers === [];
    }
}
