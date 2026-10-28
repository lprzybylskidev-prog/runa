<?php

namespace Runa\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Runa\Runtime\RuntimeHeartbeat;

final class CheckRuntimeHealth extends Command
{
    protected $signature = 'runa:runtime:health {service : queue or scheduler}';

    protected $description = 'Check instance heartbeat and runtime database readiness';

    public function handle(RuntimeHeartbeat $heartbeat): int
    {
        $service = $this->argument('service');
        if (! in_array($service, ['queue', 'scheduler'], true)) {
            $this->error('Unknown runtime service.');

            return self::FAILURE;
        }
        $age = $service === 'queue' ? 120 : 150;
        if (! $heartbeat->isFresh($service, $age)) {
            $this->error('Runtime heartbeat is missing, stale or from another container.');

            return self::FAILURE;
        }
        DB::table('shared.jobs')->limit(1)->get(['id']);
        $this->info('Runtime service is healthy.');

        return self::SUCCESS;
    }
}
