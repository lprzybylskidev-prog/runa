<?php

namespace Runa\Console\Commands\Generators;

use Illuminate\Foundation\Console\TestMakeCommand;
use Illuminate\Support\Str;

final class ApplicationTestMakeCommand extends TestMakeCommand
{
    protected function getPath($name): string
    {
        return dirname(base_path()).'/tests/backend/app'.str_replace('\\', '/', Str::replaceFirst($this->rootNamespace(), '', $name)).'.php';
    }

    protected function rootNamespace(): string
    {
        return 'ApplicationTests';
    }
}
