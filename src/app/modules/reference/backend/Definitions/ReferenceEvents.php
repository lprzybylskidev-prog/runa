<?php

namespace App\Modules\Reference\Definitions;

use Runa\Contracts\Modules\EventDefinition;
use Runa\Contracts\Modules\EventProvider;
use Runa\Contracts\Modules\ListenerDefinition;

final class ReferenceEvents implements EventProvider
{
    public function events(): array
    {
        return [new EventDefinition('observed', 1)];
    }

    public function listeners(): array
    {
        return [new ListenerDefinition('observe_sync', 'app.reference', 'observed', 1, 'sync', ReferenceListener::class), new ListenerDefinition('observe_async', 'app.reference', 'observed', 1, 'async', 'reference.event')];
    }
}
