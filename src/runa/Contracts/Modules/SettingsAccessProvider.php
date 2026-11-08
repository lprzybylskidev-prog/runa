<?php

namespace Runa\Contracts\Modules;

/** Optional pure access metadata on the source-owned SettingsProvider. */
interface SettingsAccessProvider
{
    public function access(): SettingsAccess;
}
