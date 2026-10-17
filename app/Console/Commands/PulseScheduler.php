<?php

namespace App\Console\Commands;

use App\Support\RuntimeHeartbeat;
use Illuminate\Console\Command;

final class PulseScheduler extends Command
{
    protected $signature = 'runa:runtime:pulse';

    protected $description = 'Record successful scheduled runtime execution';

    public function handle(RuntimeHeartbeat $heartbeat): int
    {
        $heartbeat->record('scheduler');

        return self::SUCCESS;
    }
}
