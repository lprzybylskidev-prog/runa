<?php

namespace Runa\Modules\Auth\Contracts;

/** Optional source-owned SettingsProvider extension. Content stays in the originating module. */
interface DeliveryProvider
{
    /** @return list<DeliveryCategory> */
    public function deliveryCategories(): array;
}
