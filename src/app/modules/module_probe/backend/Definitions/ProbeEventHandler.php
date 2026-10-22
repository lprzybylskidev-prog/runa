<?php

namespace App\Modules\ModuleProbe\Definitions;

use Illuminate\Support\Facades\Log;
use Runa\Contracts\Modules\PublicEvent;
use Runa\Contracts\Modules\WorkHandler;
use Runa\Support\Modules\Work\WorkContext;

final class ProbeEventHandler implements WorkHandler
{
    public function handle(WorkContext $context, array $payload): void
    {
        PublicEvent::fromArray($payload);
        Log::info('Probe queued fact observed.', ['code' => 'probe_async']);
        $context->checkpoint('probe_fact_observed');
    }
}
