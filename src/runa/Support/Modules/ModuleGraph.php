<?php

namespace Runa\Support\Modules;

use InvalidArgumentException;
use Runa\Contracts\Modules\GraphBlocker;
use Runa\Contracts\Modules\LifecyclePreview;
use Runa\Support\VersionCompatibility;
use RuntimeException;

/** Pure planning over one immutable registry generation; never loads module code. */
final readonly class ModuleGraph
{
    public function __construct(
        private RegistrySnapshot $snapshot,
        private string $platformVersion,
        private string $frameworkApiVersion = '1.0.0',
    ) {}

    public function preview(string $intent, string $target): LifecyclePreview
    {
        if (! in_array($intent, ['enable', 'disable', 'unregister'], true)) {
            throw new InvalidArgumentException('Unknown module lifecycle intent.');
        }
        $blockers = [];
        $affected = [];
        $state = [];
        /** @var array<string, list<string>> $dependents */
        $dependents = [];
        foreach ($this->snapshot->modules as $record) {
            if ($record->registered && $record->enabled) {
                foreach (array_keys($record->required) as $dependency) {
                    $dependents[$dependency][] = $record->id;
                }
            }
        }
        foreach ($dependents as &$ids) {
            sort($ids);
        }
        unset($ids);
        $visit = function (string $id, array $path) use (&$visit, &$blockers, &$affected, &$state, $intent, $dependents): void {
            /** @var list<string> $path Recursion starts with [] and appends only module IDs. */
            $path[] = $id;
            if (($state[$id] ?? 0) === 1) {
                $blockers[] = new GraphBlocker('required_cycle', $path);

                return;
            }
            if (($state[$id] ?? 0) === 2) {
                return;
            }
            $state[$id] = 1;
            $record = $this->snapshot->modules[$id] ?? null;
            if ($record === null || ! $record->registered) {
                $blockers[] = new GraphBlocker('not_registered', $path);
            } else {
                if ($intent === 'enable') {
                    array_push($blockers, ...$this->sourceBlockers($record, $path));
                    $edges = $record->required;
                    ksort($edges);
                    foreach ($edges as $dependency => $constraint) {
                        $edgePath = [...$path, $dependency];
                        if (! $this->directionAllowed($id, $dependency)) {
                            $blockers[] = new GraphBlocker('ownership_direction', $edgePath);

                            continue;
                        }
                        $dependencyRecord = $this->snapshot->modules[$dependency] ?? null;
                        if ($dependencyRecord !== null && ! (new VersionCompatibility)->supports($constraint, $dependencyRecord->sourceVersion)) {
                            $blockers[] = new GraphBlocker('dependency_version', $edgePath, $constraint, $dependencyRecord->sourceVersion);
                        }
                        $visit($dependency, $path);
                    }
                } elseif ($intent === 'unregister') {
                    if ($record->kind === 'core') {
                        $blockers[] = new GraphBlocker('core_unregister', $path);
                    }
                    if ($record->enabled) {
                        $blockers[] = new GraphBlocker('disable_before_unregister', $path);
                    }
                    foreach ($this->snapshot->modules as $consumer) {
                        if ($consumer->registered && isset($consumer->required[$id])) {
                            $blockers[] = new GraphBlocker('registered_required_dependent', [$id, $consumer->id]);
                        }
                    }
                } else {
                    if ($record->kind === 'core') {
                        $blockers[] = new GraphBlocker('core_disable', $path);
                    }
                    // Missing sources retain their validated edges so disabling their
                    // dependency cannot silently strand an enabled dependent.
                    foreach ($dependents[$id] ?? [] as $dependent) {
                        $visit($dependent, $path);
                    }
                }
                $affected[] = $id;
            }
            $state[$id] = 2;
        };
        $visit($target, []);
        $changes = [];
        foreach ($affected as $id) {
            if ($intent === 'unregister' || $this->snapshot->modules[$id]->enabled !== ($intent === 'enable')) {
                $changes[$id] = $intent === 'enable';
            }
        }

        return new LifecyclePreview($intent, $target, $this->snapshot->generation, $affected, $changes, $blockers);
    }

    /** Validate under the publication lock against a fresh snapshot, not a retained graph. */
    public function assertCurrent(LifecyclePreview $preview): void
    {
        if ($preview != $this->preview($preview->intent, $preview->target)) {
            throw new RuntimeException('Stale or altered module lifecycle preview; request a fresh preview.');
        }
        if (! $preview->graphReady()) {
            throw new RuntimeException('Module lifecycle graph is blocked.');
        }
    }

    /** Inspect only the declared optional edge; never recurse or add activation changes.
     * @return list<GraphBlocker>
     */
    public function optionalBlockers(string $consumer, string $dependency): array
    {
        $path = [$consumer, $dependency];
        $module = $this->snapshot->modules[$consumer] ?? null;
        if ($module === null || ! $module->registered) {
            return [new GraphBlocker('not_registered', [$consumer])];
        }
        $constraint = $module->optional[$dependency] ?? null;
        if ($constraint === null) {
            return [new GraphBlocker('optional_not_declared', $path)];
        }
        if (! $this->directionAllowed($consumer, $dependency)) {
            return [new GraphBlocker('ownership_direction', $path)];
        }
        $record = $this->snapshot->modules[$dependency] ?? null;
        if ($record === null || ! $record->registered) {
            return [new GraphBlocker('not_registered', $path)];
        }
        $blockers = $this->sourceBlockers($record, $path);
        if (! (new VersionCompatibility)->supports($constraint, $record->sourceVersion)) {
            $blockers[] = new GraphBlocker('dependency_version', $path, $constraint, $record->sourceVersion);
        }

        return $blockers;
    }

    /** @param list<string> $path
     * @return list<GraphBlocker>
     */
    private function sourceBlockers(ModuleRecord $record, array $path): array
    {
        $blockers = [];
        if (! $record->sourcePresent) {
            $blockers[] = new GraphBlocker('source_missing', $path);
        }
        foreach (['platform_version' => [$record->platformConstraint, $this->platformVersion], 'framework_api_version' => [$record->frameworkApiConstraint, $this->frameworkApiVersion]] as $code => [$constraint, $version]) {
            if (! (new VersionCompatibility)->supports($constraint, $version)) {
                $blockers[] = new GraphBlocker($code, $path, $constraint, $version);
            }
        }

        return $blockers;
    }

    private function directionAllowed(string $consumer, string $dependency): bool
    {
        return ! str_starts_with($consumer, 'runa.') || str_starts_with($dependency, 'runa.');
    }
}
