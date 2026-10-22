<?php

namespace Runa\Foundation;

use Illuminate\Foundation\Application as LaravelApplication;
use Runa\Support\MaintenanceState;

final class Application extends LaravelApplication
{
    public function getBootstrapProvidersPath()
    {
        return $this->path('providers.php');
    }

    public function isDownForMaintenance()
    {
        return parent::isDownForMaintenance() || $this->make(MaintenanceState::class)->active();
    }

    public function resourcePath($path = '')
    {
        return $this->joinPaths($this->path('resources'), $path);
    }
}
