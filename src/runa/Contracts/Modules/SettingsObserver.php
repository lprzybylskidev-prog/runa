<?php

namespace Runa\Contracts\Modules;

interface SettingsObserver
{
    /** Independent consumer: failures cannot reverse a published settings result. Deduplicate by operation/key/outcome. */
    public function changed(SettingChange $change): void;
}
