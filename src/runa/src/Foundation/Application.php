<?php

namespace Runa\Foundation;

use Illuminate\Foundation\Application as LaravelApplication;

final class Application extends LaravelApplication
{
    public function resourcePath($path = '')
    {
        return $this->joinPaths($this->basePath('runa/resources'), $path);
    }
}
