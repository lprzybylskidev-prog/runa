<?php

namespace Runa\Console\Commands;

use Illuminate\Console\Command;
use Runa\Support\RuntimeHeartbeat;

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
