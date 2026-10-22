<?php

namespace App\Modules\ModuleProbe\Definitions;

use Illuminate\Support\Facades\Log;
use Runa\Contracts\Modules\EventListener;
use Runa\Contracts\Modules\PublicEvent;

final class ProbeListener implements EventListener
{
    public function handle(PublicEvent $event, string $deliveryKey): void
    {
        Log::info('Probe synchronous fact observed.', ['code' => 'probe_sync']);
    }
}
