<?php

namespace Runa\Modules\Admin;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Explicit forward history. Owning preparation participation synchronizes grants/Audit. */
final class InitializeAdmin implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
