<?php

namespace Runa\Support\Modules;

use Runa\Contracts\Modules\GraphBlocker;
use Runa\Contracts\Modules\ModuleStatus;
use Runa\Support\VersionCompatibility;
use Throwable;

final readonly class ModuleAvailability
{
    public function __construct(private ModuleRegistry $registry, private ModuleHealth $health, private LifecyclePolicies $lifecycle, private string $platformVersion) {}

    /** @param string|list<string>|null $target
     * One operation evaluates its required graph once, including recovery before refusal.
     * @return array<string, ModuleStatus>
     */
    public function statuses(string|array|null $target = null, ?string $operation = null): array
    {
        $snapshot = $this->registry->snapshot();
        $memo = [];
        $visiting = [];
        $observed = [];
        $visit = function (string $id) use (&$visit, &$memo, &$visiting, &$observed, $snapshot, $operation): ModuleStatus {
            if (isset($memo[$id])) {
                return $memo[$id];
            }
            $record = $snapshot->modules[$id] ?? null;
            if (isset($visiting[$id]) || count($visiting) >= 128) {
                return new ModuleStatus($id, $record?->publicId, $snapshot->generation, $record?->sourceVersion, $record?->installedVersion, $record->registered ?? false, $record->enabled ?? false, $record->sourcePresent ?? false, 'unknown', false, [new GraphBlocker(isset($visiting[$id]) ? 'required_cycle' : 'required_graph_limit', [$id])]);
            }
            $visiting[$id] = true;
            $reasons = [];
            $blocked = false;
            $own = 'unknown';
            if ($record === null || ! $record->registered) {
                $blocked = true;
                $reasons[] = new GraphBlocker('not_registered', [$id]);
            } elseif (! $record->enabled || ! $record->sourcePresent) {
                $blocked = true;
                $reasons[] = new GraphBlocker(! $record->enabled ? 'disabled' : 'source_missing', [$id]);
                if ($record->sourcePresent) {
                    // Disabled is an administrative state, not a replacement own-health value.
                    $health = $this->health->inspect($record, $operation === '*' ? null : $operation);
                    $own = $health['state'];
                    $observed[$id] = $health['observations'];
                    array_push($reasons, ...$health['reasons']);
                }
            } else {
                // Refused ingress must not publish on-use observations over an active reservation.
                $fenced = in_array($id, $snapshot->fenced, true);
                $health = $this->health->inspect($record, $operation === '*' ? null : $operation, $operation === null || $fenced ? 'cached' : 'use');
                $observed[$id] = $health['observations'];
                $own = $health['state'];
                $blocked = $health['blocked'];
                $reasons = $health['reasons'];
                if ($fenced) {
                    $blocked = true;
                    $reasons[] = new GraphBlocker('transition_fenced', [$id]);
                }
                foreach ([[$record->platformConstraint, $this->platformVersion], [$record->frameworkApiConstraint, '1.0.0']] as [$constraint, $version]) {
                    if (! (new VersionCompatibility)->supports($constraint, $version)) {
                        $blocked = true;
                        $reasons[] = new GraphBlocker('runtime_version', [$id]);
                    }
                }
                try {
                    if (! $this->lifecycle->resolve($record)->acceptsDataVersion($record->installedVersion, $record->sourceVersion)) {
                        $blocked = true;
                        $reasons[] = new GraphBlocker('installed_data_incompatible', [$id]);
                    }
                } catch (Throwable) {
                    $blocked = true;
                    $reasons[] = new GraphBlocker('lifecycle_policy_unavailable', [$id]);
                }
                foreach ($fenced ? [] : $record->required as $dependency => $constraint) {
                    $status = $visit($dependency);
                    if ((str_starts_with($id, 'runa.') && ! str_starts_with($dependency, 'runa.')) || $status->sourceVersion === null || ! (new VersionCompatibility)->supports($constraint, $status->sourceVersion)) {
                        $blocked = true;
                        $reasons[] = new GraphBlocker('dependency_version_or_owner', [$id, $dependency]);
                    }
                    if (! $status->available) {
                        $blocked = true;
                        foreach ($status->reasons as $reason) {
                            if (count($reasons) >= 128) {
                                break;
                            }
                            $reasons[] = new GraphBlocker($reason->code, array_slice([$id, ...$reason->path], 0, 128));
                        }
                    }
                }
            }
            unset($visiting[$id]);

            return $memo[$id] = new ModuleStatus($id, $record?->publicId, $snapshot->generation, $record?->sourceVersion, $record?->installedVersion, $record->registered ?? false, $record->enabled ?? false, $record->sourcePresent ?? false, $own, ! $blocked, array_slice($reasons, 0, 128));
        };
        foreach ($target === null ? array_keys($snapshot->modules) : (is_array($target) ? $target : [$target]) as $id) {
            $visit($id);
        }
        // A concurrent lifecycle/configuration change cannot authorize an operation over old flags.
        $current = $this->registry->snapshot();
        foreach ($memo as $id => $status) {
            $old = $snapshot->modules[$id] ?? null;
            $new = $current->modules[$id] ?? null;
            $healthExpired = $new !== null && $status->available && $this->health->inspect($new, $operation === '*' ? null : $operation)['blocked'];
            if ($healthExpired || ($old !== null && ($new === null || $old->publicId !== $new->publicId || $old->registered !== $new->registered || $old->enabled !== $new->enabled || $old->sourcePresent !== $new->sourcePresent || $old->manifestHash !== $new->manifestHash || $old->healthRevision !== $new->healthRevision || $old->installedVersion !== $new->installedVersion || (isset($observed[$id]) && $observed[$id] != $new->healthObservations)) || $snapshot->fenced !== $current->fenced)) {
                foreach ($memo as $key => $value) {
                    $memo[$key] = new ModuleStatus($value->moduleId, $value->publicId, $current->generation, $value->sourceVersion, $value->installedVersion, $value->registered, $value->enabled, $value->sourcePresent, 'unknown', false, [new GraphBlocker('availability_changed', [$key])]);
                }
                break;
            }
        }

        foreach ($memo as $key => $value) {
            $memo[$key] = new ModuleStatus($value->moduleId, $value->publicId, $current->generation, $value->sourceVersion, $value->installedVersion, $value->registered, $value->enabled, $value->sourcePresent, $value->ownHealth, $value->available, $value->reasons);
        }

        return $memo;
    }
}
