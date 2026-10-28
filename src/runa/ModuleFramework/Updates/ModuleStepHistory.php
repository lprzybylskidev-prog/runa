<?php

namespace Runa\ModuleFramework\Updates;

use Runa\Contracts\Modules\UpgradeDefinition;
use RuntimeException;

final class ModuleStepHistory
{
    public static function key(UpgradeDefinition $step): string
    {
        return 'upgrade:'.$step->key.':'.($step->from ?? 'install').':'.$step->to;
    }

    public static function fingerprint(string $root, PlannedModule $module, UpgradeDefinition $step): string
    {
        $file = $root.'/'.$module->source->path.'/backend/'.str_replace('\\', '/', substr($step->handler, strlen($module->source->manifest->prefix))).'.php';

        return hash('sha256', json_encode((array) $step, JSON_THROW_ON_ERROR).hash_file('sha256', $file));
    }

    /** @param list<array{module: string, step: string, fingerprint: string, status: string}> $history
     * @param  list<PlannedModule>  $plan
     */
    public static function validate(string $root, array $plan, array $history): void
    {
        foreach ($plan as $module) {
            $upgrades = [];
            foreach ($module->steps as $step) {
                $upgrades[self::key($step)] = $step;
            }
            foreach ($history as $row) {
                if ($row['module'] !== $module->source->manifest->id) {
                    continue;
                }
                if (str_starts_with($row['step'], 'migration:') && ($module->migrations[substr($row['step'], 10)] ?? null) !== $row['fingerprint']) {
                    throw new RuntimeException('Applied module migration is changed or missing.');
                }
                $step = $upgrades[$row['step']] ?? null;
                if ($step !== null) {
                    if (self::fingerprint($root, $module, $step) !== $row['fingerprint']) {
                        throw new RuntimeException('Recorded module step source changed.');
                    }
                    if ($row['status'] !== 'succeeded' && ! $step->transactional && ! $step->idempotent) {
                        throw new RuntimeException('Ambiguous non-idempotent module step requires operator recovery; replay refused.');
                    }
                }
            }
        }
    }
}
