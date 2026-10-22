<?php

namespace App\Modules\Reference\Definitions;

use Illuminate\Support\Facades\Log;
use Runa\Contracts\Modules\EventListener;
use Runa\Contracts\Modules\PublicEvent;

final class ReferenceListener implements EventListener
{
    public function handle(PublicEvent $event, string $deliveryKey): void
    {
        Log::info('Reference synchronous fact observed.', ['code' => 'reference_sync']);
    }
}
