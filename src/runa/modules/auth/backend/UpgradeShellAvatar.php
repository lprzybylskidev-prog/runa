<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Guarded shell profile presentation and shared avatar; no stored data or settings change. */
final class UpgradeShellAvatar implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
