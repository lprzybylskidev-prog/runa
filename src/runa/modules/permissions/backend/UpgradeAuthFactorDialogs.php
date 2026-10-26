<?php

namespace Runa\Modules\Permissions;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Account factor dialogs and compatible trust presentation; no stored data changes. */
final class UpgradeAuthFactorDialogs implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
