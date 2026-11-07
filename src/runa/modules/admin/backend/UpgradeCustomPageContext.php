<?php

namespace Runa\Modules\Admin;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Additive custom-page frontend API; preserves all stored state. */
final class UpgradeCustomPageContext implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
