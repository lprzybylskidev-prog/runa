<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;
use Runa\Modules\Auth\Actions\PolicyHistory;
use Runa\Support\Modules\Settings\ModuleSettings;

final class UpgradePolicy implements UpgradeHandler
{
    public function run(UpgradeContext $context): void
    {
        foreach (['policy_versions', 'policy_exceptions', 'operator_tokens', 'initialization'] as $table) {
            if (! $context->connection->getSchemaBuilder()->hasTable('runa_auth.'.$table)) {
                throw new \RuntimeException('Auth policy/operator storage unavailable.');
            }
        }
        app(ModuleSettings::class)->upgradeDefaults($context);
        app(PolicyHistory::class)->baseline($context->connection);
    }
}
