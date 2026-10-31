<?php

namespace Runa\Contracts\Settings;

interface ApplicationSettingProvider
{
    /** @return list<ApplicationSetting> */
    public function settings(): array;

    /** @return list<ApplicationLogo> */
    public function logos(): array;
}
