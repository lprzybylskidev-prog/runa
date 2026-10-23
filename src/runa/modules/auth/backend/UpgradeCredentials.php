<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;
use Runa\Support\Modules\Settings\ModuleSettings;

final class UpgradeCredentials implements UpgradeHandler
{
    public function run(UpgradeContext $context): void
    {
        foreach (['password_history', 'step_up_proofs'] as $table) {
            if (! $context->connection->getSchemaBuilder()->hasTable('runa_auth.'.$table)) {
                throw new \RuntimeException('Auth credential storage unavailable.');
            }
        }
        app(ModuleSettings::class)->upgradeDefaults($context);
    }
}
