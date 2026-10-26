<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Programmatic heading focus presentation correction; no stored data or settings change. */
final class UpgradeHeadingFocus implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
