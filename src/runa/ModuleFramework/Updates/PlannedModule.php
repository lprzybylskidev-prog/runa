<?php

namespace Runa\ModuleFramework\Updates;

use Runa\Contracts\Modules\UpgradeDefinition;
use Runa\ModuleFramework\ModuleSource;

final readonly class PlannedModule
{
    /** @param array<string, string> $migrations Name => SHA256
     * @param  list<UpgradeDefinition>  $steps
     * @param  list<string>  $completed
     */
    public function __construct(public ModuleSource $source, public ?string $installed, public array $migrations, public array $steps, public string $fingerprint, public array $completed = []) {}

    /** @param list<array{module: string, step: string, fingerprint: string, status: string}> $history */
    public function withHistory(array $history): self
    {
        $completed = array_values(array_map(fn (array $row): string => $row['step'], array_filter($history, fn (array $row): bool => $row['module'] === $this->source->manifest->id && $row['status'] === 'succeeded')));

        return new self($this->source, $this->installed, $this->migrations, $this->steps, $this->fingerprint, $completed);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['module' => $this->source->manifest->id, 'from' => $this->installed, 'to' => $this->source->manifest->version,
            'migrations' => $this->migrations, 'upgrades' => array_map(fn (UpgradeDefinition $step): array => (array) $step, $this->steps),
            'fingerprint' => $this->fingerprint, 'completed_steps' => $this->completed,
            'pending_migrations' => array_filter($this->migrations, fn (string $name): bool => ! in_array('migration:'.$name, $this->completed, true), ARRAY_FILTER_USE_KEY),
            'pending_upgrades' => array_values(array_map(fn (UpgradeDefinition $step): string => $step->key, array_filter($this->steps, fn (UpgradeDefinition $step): bool => ! in_array(ModuleStepHistory::key($step), $this->completed, true))))];
    }
}
