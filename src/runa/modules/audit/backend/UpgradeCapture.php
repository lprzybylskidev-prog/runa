<?php

namespace Runa\Modules\Audit;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** No data transformation; migrations own additive read indexes. */
final class UpgradeCapture implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
