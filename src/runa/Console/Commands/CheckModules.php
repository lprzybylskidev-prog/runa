<?php

namespace Runa\Console\Commands;

use Illuminate\Console\Command;
use Runa\Support\Modules\Development\ModuleContractSuite;
use Runa\Support\Modules\ModuleDiscovery;
use Runa\Support\Modules\ModuleRegistry;
use Throwable;

final class CheckModules extends Command
{
    protected $signature = 'runa:modules:check {owner : app or runa} {--runtime : Validate synchronized trusted runtime descriptors without executing operations}';

    protected $description = 'Check source ownership, manifest, required graph and optional runtime module contracts';

    public function handle(ModuleRegistry $registry): int
    {
        try {
            if (! app()->environment(['local', 'testing'])) {
                throw new \RuntimeException('Module development contracts require local/testing context.');
            }
            $development = app()->environment(['local', 'testing']);
            $sources = (new ModuleDiscovery(base_path(), $development))->discover($this->argument('owner'));
            $suite = new ModuleContractSuite(base_path(), $development);
            $snapshot = $this->option('runtime') ? $registry->snapshot() : null;
            $failed = false;
            foreach ($sources as $source) {
                $errors = $suite->source($source->manifest->id);
                if ($snapshot !== null) {
                    $record = $snapshot->modules[$source->manifest->id] ?? null;
                    $errors = [...$errors, ...($record === null ? ['runtime:not_registered'] : $suite->runtime($record))];
                }
                $this->line(json_encode(['module' => $source->manifest->id, 'errors' => $errors], JSON_THROW_ON_ERROR));
                $failed = $failed || $errors !== [];
            }

            return $failed ? self::FAILURE : self::SUCCESS;
        } catch (Throwable) {
            $this->error('Module contracts failed. Check source layout, manifest, graph and synchronization.');

            return self::FAILURE;
        }
    }
}
