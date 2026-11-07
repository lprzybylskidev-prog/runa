<?php

namespace Runa\Modules\Audit;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Current-schema update; native preparation owns declaration synchronization and Audit. */
final class UpdateAudit implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
