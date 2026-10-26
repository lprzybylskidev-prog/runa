<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;
use Runa\Support\Modules\Settings\ModuleSettings;

final class UpgradePreferences implements UpgradeHandler
{
    public function run(UpgradeContext $context): void
    {
        foreach (['preferences', 'delivery_preferences', 'security_notices', 'notice_frequencies', 'known_browsers'] as $table) {
            if (! $context->connection->getSchemaBuilder()->hasTable('runa_auth.'.$table)) {
                throw new \RuntimeException('Auth preferences/delivery storage unavailable.');
            }
        }
        app(ModuleSettings::class)->upgradeDefaults($context);
    }
}
