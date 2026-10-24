<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Additive presentation contracts; no stored data or settings change. */
final class UpgradePresentation implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
