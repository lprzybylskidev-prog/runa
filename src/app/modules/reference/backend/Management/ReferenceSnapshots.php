<?php

namespace App\Modules\Reference\Management;

use Runa\Support\Modules\Settings\SettingsSnapshotSection;

/** Committed public label only; credentials never enter snapshots. */
final readonly class ReferenceSnapshots extends SettingsSnapshotSection
{
    protected const string MODULE = 'app.reference';

    protected const string SCOPE = 'label';

    protected const string READ_PERMISSION = 'app.reference.settings.read';

    protected const string WRITE_PERMISSION = 'app.reference.settings.write';

    protected const string DELETE_ACTION = 'app.reference.settings.snapshot_delete';

    public static function keys(): array
    {
        return ['label'];
    }
}
