<?php

namespace Runa\Modules\Auth\Management;

use Runa\Support\Modules\Settings\SettingsSnapshotSection;

final readonly class PolicySnapshots extends SettingsSnapshotSection
{
    protected const string MODULE = 'runa.auth';

    protected const string SCOPE = 'policy';

    protected const string READ_PERMISSION = 'runa.auth.policy.read';

    protected const string WRITE_PERMISSION = 'runa.auth.policy.write';

    protected const string DELETE_ACTION = 'runa.auth.policy.snapshot_delete';

    public static function keys(): array
    {
        return PolicySection::keys();
    }
}
