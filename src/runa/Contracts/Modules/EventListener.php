<?php

namespace Runa\Contracts\Modules;

interface EventListener
{
    /** Independent, idempotent by delivery key. Critical coordinated calls use ModuleApis. */
    public function handle(PublicEvent $event, string $deliveryKey): void;
}
