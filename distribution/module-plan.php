<?php

use Runa\Support\Modules\ModuleDiscovery;
use Runa\Support\Modules\Updates\ModuleStepHistory;
use Runa\Support\Modules\Updates\ModuleUpdatePlanner;

// Isolated read-only target-release planner. No Laravel boot, database, network or writable source mounts.
try {
    $installed = $argv[1] ?? '/installed';
    $root = $argv[2] ?? '/target';
    require $installed.'/vendor/autoload.php';
    spl_autoload_register(static function (string $class) use ($root): void {
        if (str_starts_with($class, 'Runa\\') && ! str_starts_with($class, 'Runa\\Modules\\')) {
            $path = $root.'/runa/'.str_replace('\\', '/', substr($class, 5)).'.php';
            if (is_file($path)) {
                require $path;
            }
        }
    }, prepend: true);
    $sources = (new ModuleDiscovery($root, true))->discover('runa');
    spl_autoload_register(static function (string $class) use ($sources, $root): void {
        foreach ($sources as $source) {
            if (str_starts_with($class, $source->manifest->prefix)) {
                $path = $root.'/'.$source->path.'/backend/'.str_replace('\\', '/', substr($class, strlen($source->manifest->prefix))).'.php';
                require_once ModuleDiscovery::ownedPath($root.'/'.$source->path, $path);

                return;
            }
        }
    }, prepend: true);
    $input = json_decode(stream_get_contents(STDIN), true, 32, JSON_THROW_ON_ERROR);
    $plan = (new ModuleUpdatePlanner($root, $input['platform'], true))->plan('runa', $input['modules']);
    ModuleStepHistory::validate($root, $plan, $input['module_steps']);
    echo json_encode(array_map(static fn ($module) => $module->withHistory($input['module_steps'])->toArray(), $plan), JSON_THROW_ON_ERROR);
} catch (Throwable) {
    fwrite(STDERR, "Module target preflight failed: inspect versions, dependencies, upgrade paths and source-owned preflight contracts.\n");
    exit(1);
}
