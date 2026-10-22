<?php

namespace App\Modules\Reference\Definitions;

use Illuminate\Support\Facades\Log;
use Runa\Contracts\Modules\PublicEvent;
use Runa\Contracts\Modules\WorkHandler;
use Runa\Support\Modules\Work\WorkContext;

final class ReferenceEventHandler implements WorkHandler
{
    public function handle(WorkContext $context, array $payload): void
    {
        PublicEvent::fromArray($payload);
        Log::info('Reference queued fact observed.', ['code' => 'reference_async']);
        $context->checkpoint('reference_fact_observed');
    }
}
