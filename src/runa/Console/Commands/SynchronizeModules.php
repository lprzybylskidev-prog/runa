<?php

namespace Runa\Console\Commands;

use Illuminate\Console\Command;
use Runa\Support\Modules\ModuleRegistry;

final class SynchronizeModules extends Command
{
    protected $signature = 'runa:modules:sync {owner : Explicit source owner: runa or app}';

    protected $description = 'Synchronize module source metadata without enabling Optional modules or installing data';

    public function handle(ModuleRegistry $registry): int
    {
        $owner = $this->argument('owner');
        if (! in_array($owner, ['runa', 'app'], true)) {
            $this->error('Select the runa or app source owner.');

            return self::FAILURE;
        }
        $snapshot = $registry->synchronize($owner);
        $this->info('Module registry synchronized at generation '.$snapshot->generation.'.');

        return self::SUCCESS;
    }
}
