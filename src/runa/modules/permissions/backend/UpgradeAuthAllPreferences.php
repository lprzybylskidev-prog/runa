<?php

namespace Runa\Modules\Permissions;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Additive own-account preference reset and compatible presentation; no schema changes. */
final class UpgradeAuthAllPreferences implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
