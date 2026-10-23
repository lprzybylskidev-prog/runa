<?php

namespace Runa\Support\Modules\Settings;

use Runa\Contracts\Modules\SettingChange;
use Runa\Contracts\Modules\SettingsObserver;

final class IgnoreSettingsChanges implements SettingsObserver
{
    public function changed(SettingChange $change): void {}
}
