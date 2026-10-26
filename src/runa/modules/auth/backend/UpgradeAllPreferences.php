<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Additive own-account preference reset and compatible presentation; no schema changes. */
final class UpgradeAllPreferences implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
