<?php

namespace Runa\Foundation;

use Illuminate\Foundation\Application as LaravelApplication;

final class Application extends LaravelApplication
{
    // Explicit namespace while Composer's repository-relative manifest bridges point 4.
    public function getNamespace(): string
    {
        return 'App\\';
    }
}
