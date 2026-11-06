<?php

namespace Runa\Modules\Auth;

use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

/** No default user/credential/admin flag. Preparation already records the Audit boundary. */
final class InitializeAuth implements UpgradeHandler
{
    public function run(UpgradeContext $context): void
    {
        if (! $context->connection->getSchemaBuilder()->hasTable('runa_auth.users')) {
            throw new \RuntimeException('Auth identity storage unavailable.');
        }
        foreach (['notice_scan' => ['last_user_id' => 0], 'initialization' => ['subject' => null, 'completed_at' => null]] as $table => $values) {
            if (! $context->connection->table('runa_auth.'.$table)->exists()) {
                $context->connection->table('runa_auth.'.$table)->insert($values);
            }
        }
    }
}
