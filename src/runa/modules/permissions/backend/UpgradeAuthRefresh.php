<?php

namespace Runa\Modules\Permissions;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Compatible account refresh and preference coordination; no schema changes. */
final class UpgradeAuthRefresh implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
