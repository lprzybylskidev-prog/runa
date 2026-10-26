<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Compatible preference layout and recipient selection repair; no schema changes. */
final class UpgradePreferencesSelection implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
