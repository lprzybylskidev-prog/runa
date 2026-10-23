<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;
use Runa\Support\Modules\Settings\ModuleSettings;

final class UpgradeEntry implements UpgradeHandler
{
    public function run(UpgradeContext $context): void
    {
        if (! $context->connection->getSchemaBuilder()->hasTable('runa_auth.entry_tokens')) {
            throw new \RuntimeException('Auth entry storage unavailable.');
        }
        app(ModuleSettings::class)->upgradeDefaults($context);
    }
}
