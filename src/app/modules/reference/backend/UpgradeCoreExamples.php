<?php

namespace App\Modules\Reference;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** Source/API/grant change only. Preserve existing rows, identities and settings. */
final class UpgradeCoreExamples implements UpgradeHandler
{
    public function run(UpgradeContext $context): void {}
}
