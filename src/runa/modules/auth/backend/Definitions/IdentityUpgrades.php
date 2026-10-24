<?php

namespace Runa\Modules\Auth\Definitions;

use Runa\Contracts\Modules\UpgradeDefinition;
use Runa\Contracts\Modules\UpgradeProvider;
use Runa\Modules\Auth\InitializeAuth;
use Runa\Modules\Auth\UpgradeCredentials;
use Runa\Modules\Auth\UpgradeEntry;
use Runa\Modules\Auth\UpgradePolicy;
use Runa\Modules\Auth\UpgradePreferences;
use Runa\Modules\Auth\UpgradeProfiles;
use Runa\Modules\Auth\UpgradeSessions;

final class IdentityUpgrades implements UpgradeProvider
{
    public function upgrades(): array
    {
        return [new UpgradeDefinition('initialize', null, '0.1.0', InitializeAuth::class),
            new UpgradeDefinition('entry', '0.1.0', '0.2.0', UpgradeEntry::class),
            new UpgradeDefinition('sessions', '0.2.0', '0.3.0', UpgradeSessions::class),
            new UpgradeDefinition('credentials', '0.3.0', '0.4.0', UpgradeCredentials::class),
            new UpgradeDefinition('profiles', '0.4.0', '0.5.0', UpgradeProfiles::class),
            new UpgradeDefinition('preferences', '0.5.0', '0.6.0', UpgradePreferences::class),
            new UpgradeDefinition('policy', '0.6.0', '0.7.0', UpgradePolicy::class)];
    }

    public function preflight(?string $installed, string $target): ?string
    {
        return null;
    }
}
