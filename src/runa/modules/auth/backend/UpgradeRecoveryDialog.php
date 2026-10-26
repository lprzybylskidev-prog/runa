<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Additive private recovery modal presentation; no stored data or settings change. */
final class UpgradeRecoveryDialog implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
