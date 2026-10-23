<?php

namespace App\Modules\Reference;

use Illuminate\Support\Str;
use Runa\Contracts\Modules\UpgradeContext;
use Runa\Contracts\Modules\UpgradeHandler;

final class InitializeReference implements UpgradeHandler
{
    public function run(UpgradeContext $context): void
    {
        // Transactional and repeat safe; never invoked by ordinary enable/disable.
        $context->connection->table('app_reference.samples')->insertOrIgnore([
            'public_id' => (string) Str::uuid7(), 'code' => 'welcome',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
