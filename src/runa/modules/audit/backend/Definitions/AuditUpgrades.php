<?php

namespace Runa\Modules\Audit\Definitions;

use Runa\Contracts\Modules\UpgradeDefinition;
use Runa\Contracts\Modules\UpgradeProvider;
use Runa\Modules\Audit\InitializeAudit;
use Runa\Modules\Audit\UpgradeCapture;

final class AuditUpgrades implements UpgradeProvider
{
    public function upgrades(): array
    {
        return [new UpgradeDefinition('initialize', null, '0.1.0', InitializeAudit::class),
            new UpgradeDefinition('capture', '0.1.0', '0.2.0', UpgradeCapture::class)];
    }

    public function preflight(?string $installed, string $target): ?string
    {
        return null;
    }
}
