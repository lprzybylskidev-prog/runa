<?php

namespace Runa\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Runa\Database\Seeders\DatabaseSeeder;
use Runa\Support\Seeding\SeedGraph;

final class SeedProject extends Command
{
    use ConfirmableTrait;

    protected $signature = 'runa:seed';

    protected $description = 'Run the validated production/development/occasional seeder dependency graph';

    public function __construct(private readonly string $mode = 'default')
    {
        $this->signature = 'runa:seed'.($mode === 'default' ? '' : ':'.($mode === 'graph' ? 'plan' : $mode));
        $this->signature .= $mode === 'occasional' ? ' {scenario}' : '';
        if ($mode !== 'graph') {
            $this->signature .= ' {--force : Run without interactive production confirmation}';
        }
        $this->description = $mode === 'graph' ? 'Show the seeder dependency graph without executing it' : 'Run the '.$mode.' seeder selection';
        parent::__construct();
    }

    public function handle(): int
    {
        $mode = $this->mode;
        $scenario = $mode === 'occasional' ? $this->input->getArgument('scenario') : null;
        if ($scenario !== null && ! is_string($scenario)) {
            throw new \InvalidArgumentException('A seeder scenario must be a string.');
        }
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
