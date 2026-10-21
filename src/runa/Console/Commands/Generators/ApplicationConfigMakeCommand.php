<?php

namespace Runa\Console\Commands\Generators;

use Illuminate\Foundation\Console\ConfigMakeCommand;
use Illuminate\Support\Str;

final class ApplicationConfigMakeCommand extends ConfigMakeCommand
{
    protected function getPath($name): string
    {
        return app_path('config/'.Str::finish($this->argument('name'), '.php'));
    }
}
