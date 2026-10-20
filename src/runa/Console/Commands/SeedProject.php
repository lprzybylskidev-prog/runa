<?php

namespace Runa\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Runa\Database\Seeders\DatabaseSeeder;
use Runa\Support\Seeding\SeedGraph;

final class SeedProject extends Command
{
    use ConfirmableTrait;

    protected $signature = 'runa:seed {mode=default} {scenario?} {--force : Run production seeders without interactive confirmation}';

    protected $description = 'Run the validated production/development/occasional seeder dependency graph';

    public function handle(): int
    {
        $mode = $this->argument('mode');
        $scenario = $this->argument('scenario');
        $plan = (new SeedGraph(SeedGraph::discover()))->plan($mode, app()->environment(), $scenario);
        if ($mode === 'graph') {
            foreach ($plan as $class) {
                $this->line($class::category()->value.' '.$class.' dependsOn='.implode(',', $class::dependsOn()));
            }
        } else {
            if (! $this->confirmToProceed()) {
                return self::FAILURE;
            }
            app(DatabaseSeeder::class)->setContainer(app())->setCommand($this)->call($plan);
        }
        $this->info(count($plan).' seeders '.($mode === 'graph' ? 'in graph.' : 'completed.'));

        return self::SUCCESS;
    }
}
