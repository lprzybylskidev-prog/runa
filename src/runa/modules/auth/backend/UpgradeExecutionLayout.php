<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Private HTTP/work placement changed; no schema or data transformation. */
final class UpgradeExecutionLayout implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
