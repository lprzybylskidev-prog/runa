<?php

namespace Runa\Modules\Auth\Management;

use Runa\Support\Modules\Settings\SettingsSnapshotSection;

final readonly class PolicySnapshots extends SettingsSnapshotSection
{
    protected const MODULE = 'runa.auth';

    protected const SCOPE = 'policy';

    protected const READ_PERMISSION = 'runa.auth.policy.read';

    protected const WRITE_PERMISSION = 'runa.auth.policy.write';

    protected const DELETE_ACTION = 'runa.auth.policy.snapshot_delete';

    public static function keys(): array
    {
        return PolicySection::keys();
    }
}
