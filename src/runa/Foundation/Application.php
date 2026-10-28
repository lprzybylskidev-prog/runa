<?php

namespace Runa\Foundation;

use Illuminate\Foundation\Application as LaravelApplication;
use Runa\Runtime\MaintenanceState;
use Runa\Runtime\RuntimeHeartbeat;

final class Application extends LaravelApplication
{
    public function getBootstrapProvidersPath()
    {
        return $this->path('providers.php');
    }

    public function isDownForMaintenance()
    {
        // Only the actual queue daemon owns this context; diagnostic/probe processes never set it.
        if ($this->bound('runa.queue_worker_active') && $this->make('runa.queue_worker_active') === true) {
            $this->make(RuntimeHeartbeat::class)->record('queue');
        }

        return parent::isDownForMaintenance() || $this->make(MaintenanceState::class)->active();
    }

    public function resourcePath($path = '')
    {
        return $this->joinPaths($this->path('resources'), $path);
    }
}
