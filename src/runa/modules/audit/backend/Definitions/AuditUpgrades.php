<?php

namespace Runa\Modules\Audit\Definitions;

use Runa\Contracts\Modules\UpgradeDefinition;
use Runa\Contracts\Modules\UpgradeProvider;
use Runa\Modules\Audit\InitializeAudit;
use Runa\Modules\Audit\UpdateAudit;

final class AuditUpgrades implements UpgradeProvider
{
    public function upgrades(): array
    {
        return [
            new UpgradeDefinition('initialize', null, '1.1.2', InitializeAudit::class),
            new UpgradeDefinition('current_initialization', '1.1.1', '1.1.2', UpdateAudit::class),
        ];
    }

    public function preflight(?string $installed, string $target): ?string
    {
        return null;
    }
}
