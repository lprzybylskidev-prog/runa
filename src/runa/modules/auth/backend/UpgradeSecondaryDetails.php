<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Additive private secondary-address details presentation; no stored data or settings change. */
final class UpgradeSecondaryDetails implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
