<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;
use Runa\Support\Modules\Settings\ModuleSettings;

final class UpgradeAccountUi implements UpgradeHandler
{
    public function run(UpgradeContext $context): void
    {
        app(ModuleSettings::class)->upgradeDefaults($context);
    }
}
