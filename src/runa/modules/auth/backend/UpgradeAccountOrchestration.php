<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Internal account orchestration changes require no schema or data transformation. */
final class UpgradeAccountOrchestration implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
