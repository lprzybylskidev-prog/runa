<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Explicit source-only edge: no stored data or settings change. */
final class UpgradeEntryUi implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
