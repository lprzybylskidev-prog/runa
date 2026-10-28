<?php

namespace Runa\ModuleFramework\Frontend;

use Illuminate\Database\Connection;
use Illuminate\Routing\Router;
use Runa\Http\Controllers\ModulePageController;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\Support\Modules\Frontend\ModuleFrontend;

final readonly class ModuleFrontendRoutes
{
    public function __construct(private Connection $connection, private ModuleRegistry $registry, private ModuleFrontend $frontend, private Router $router) {}

    public function register(): void
    {
        try {
            if (! $this->connection->getSchemaBuilder()->hasTable('shared.module_registry_state')) {
                return;
            }
            $records = $this->registry->snapshot()->modules;
        } catch (\Throwable) {
            return;
        }
        $planned = [];
        foreach ($records as $record) {
            if (! $record->registered || ! $record->sourcePresent) {
                continue;
            }
            try {
                $pages = $this->frontend->pages($record);
            } catch (\Throwable) {
                continue;
            }
            foreach ($pages as $page) {
                $uri = 'modules/'.str_replace('.', '/', $record->id).'/'.$page->key;
                $name = $record->id.'.pages.'.$page->key;
                foreach ($this->router->getRoutes()->getRoutes() as $existing) {
                    if ($existing->getName() === $name || ($existing->uri() === $uri && in_array('GET', $existing->methods(), true))) {
                        throw new \RuntimeException('Duplicate module page route.');
                    }
                }
                $planned[] = [$uri, $name];
            }
        }
        foreach ($planned as [$uri, $name]) {
            $this->router->get($uri, ModulePageController::class.'@__invoke')->name($name);
        }
    }
}
