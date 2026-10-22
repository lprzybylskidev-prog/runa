<?php

namespace App\Modules\ModuleProbe\Definitions;

use Runa\Contracts\Modules\EventListener;
use Runa\Contracts\Modules\PublicEvent;

final class ProbeListener implements EventListener
{
    public function handle(PublicEvent $event, string $deliveryKey): void
    {
        // Observe the public envelope without a business effect or retained module data.
    }
}
