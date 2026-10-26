<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Departing-document cancellation correction; no stored data or settings change. */
final class UpgradeNavigationCancellation implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
