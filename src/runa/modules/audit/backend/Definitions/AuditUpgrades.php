<?php

namespace Runa\Modules\Audit\Definitions;

use Runa\Contracts\Modules\UpgradeDefinition;
use Runa\Contracts\Modules\UpgradeProvider;
use Runa\Modules\Audit\InitializeAudit;

final class AuditUpgrades implements UpgradeProvider
{
    public function upgrades(): array
    {
        return [new UpgradeDefinition('initialize', null, '0.1.0', InitializeAudit::class)];
    }

    public function preflight(?string $installed, string $target): ?string
    {
        return null;
    }
}
