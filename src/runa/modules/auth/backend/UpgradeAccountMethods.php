<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Account method controls and avatar confirmation; no stored data or settings change. */
final class UpgradeAccountMethods implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
