<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;
use Runa\Support\Modules\Settings\ModuleSettings;

final class UpgradeSessions implements UpgradeHandler
{
    public function run(UpgradeContext $context): void
    {
        foreach (['sessions', 'trusted_devices', 'login_windows'] as $table) {
            if (! $context->connection->getSchemaBuilder()->hasTable('runa_auth.'.$table)) {
                throw new \RuntimeException('Auth session storage unavailable.');
            }
        }
        app(ModuleSettings::class)->upgradeDefaults($context);
    }
}
