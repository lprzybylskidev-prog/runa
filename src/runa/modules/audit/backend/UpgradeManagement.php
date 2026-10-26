<?php

namespace Runa\Modules\Audit;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

final class UpgradeManagement implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
