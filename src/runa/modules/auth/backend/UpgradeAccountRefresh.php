<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Compatible account refresh and preference coordination; no schema changes. */
final class UpgradeAccountRefresh implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
