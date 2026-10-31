<?php

namespace App\Modules\Reference;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Source-only health adopts schema 2 without changing module-domain data. */
final class UpgradeSourceHealth implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
