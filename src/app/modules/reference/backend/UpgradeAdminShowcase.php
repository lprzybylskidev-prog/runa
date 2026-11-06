<?php

namespace App\Modules\Reference;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Add read-only Admin declarations without changing existing data or settings. */
final class UpgradeAdminShowcase implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
