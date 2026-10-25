<?php

namespace Runa\Modules\Audit\Definitions;

use Runa\Contracts\Modules\UpgradeDefinition;
use Runa\Contracts\Modules\UpgradeProvider;
use Runa\Modules\Audit\InitializeAudit;
use Runa\Modules\Audit\UpgradeCapture;
use Runa\Modules\Audit\UpgradeManagement;
use Runa\Modules\Audit\UpgradePermissionDeclarations;

final class AuditUpgrades implements UpgradeProvider
{
    public function upgrades(): array
    {
        return [new UpgradeDefinition('initialize', null, '0.1.0', InitializeAudit::class),
            new UpgradeDefinition('capture', '0.1.0', '0.2.0', UpgradeCapture::class),
            new UpgradeDefinition('own_history', '0.2.0', '0.3.0', UpgradeCapture::class),
            new UpgradeDefinition('permission_declarations', '0.3.0', '0.4.0', UpgradePermissionDeclarations::class),
            new UpgradeDefinition('management', '0.4.0', '0.5.0', UpgradeManagement::class),
            new UpgradeDefinition('own_history_details', '0.5.0', '0.6.0', UpgradeCapture::class)];
    }

    public function preflight(?string $installed, string $target): ?string
    {
        return null;
    }
}
