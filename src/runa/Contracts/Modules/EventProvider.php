<?php

namespace Runa\Contracts\Modules;

/** Pure, source-owned declarations; never register raw Laravel module listeners. */
interface EventProvider
{
    /** @return list<EventDefinition> */
    public function events(): array;

    /** @return list<ListenerDefinition> */
    public function listeners(): array;
}
