<?php

namespace Runa\Console\Commands;

use Illuminate\Console\Command;
use Runa\Support\Modules\Management\ModuleManagement;
use Throwable;

final class DescribeModuleManagement extends Command
{
    protected $signature = 'runa:modules:management {module}';

    protected $description = 'Read trusted versioned module management metadata without executing actions';

    public function handle(ModuleManagement $management): int
    {
        try {
            $module = $this->argument('module');
            $view = $management->read($module);
            $this->line(json_encode($view, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

            return $view->diagnostics === [] ? self::SUCCESS : self::FAILURE;
        } catch (Throwable) {
            $this->error('Module management metadata unavailable. Check registration, source and registry generation.');

            return self::FAILURE;
        }
    }
}
