<?php

namespace App\Modules\ModuleProbe\Definitions;

use Runa\Contracts\Modules\EventDefinition;
use Runa\Contracts\Modules\EventProvider;
use Runa\Contracts\Modules\ListenerDefinition;

final class ProbeEvents implements EventProvider
{
    public function events(): array
    {
        return [new EventDefinition('observed', 1)];
    }

    public function listeners(): array
    {
        return [new ListenerDefinition('observe_sync', 'app.module_probe', 'observed', 1, 'sync', ProbeListener::class), new ListenerDefinition('observe_async', 'app.module_probe', 'observed', 1, 'async', 'probe.event')];
    }
}
