<?php

namespace App\Modules\Reference;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Compatible frontend source contracts require no schema or data transformation. */
final class UpgradePublicHttp implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
