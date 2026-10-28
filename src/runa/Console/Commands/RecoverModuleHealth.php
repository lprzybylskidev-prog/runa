<?php

namespace Runa\Console\Commands;

use Illuminate\Console\Command;
use Runa\ModuleFramework\ModuleHealth;

/** Technical recovery is exempt from business maintenance/availability gates. */
final class RecoverModuleHealth extends Command
{
    protected $signature = 'runa:modules:health {module?}';

    protected $description = 'Evaluate due scheduled/hybrid module health checks without changing enabled flags';

    public function handle(ModuleHealth $health): int
    {
        $id = $this->argument('module');
        if (is_string($id)) {
            $result = $health->diagnose($id);
            $this->components->info('Module health: '.$result['state']);

            return $result['blocked'] ? self::FAILURE : self::SUCCESS;
        }
        $health->recover();
        $this->components->info('Module health recovery pass completed.');

        return self::SUCCESS;
    }
}
