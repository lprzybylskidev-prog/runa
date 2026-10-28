<?php

namespace Runa\ModuleFramework\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Runa\Support\Modules\Api\OperationRejected;

/** No module code executes when source declarations require recovery/synchronization. */
final class ModuleHttpUnavailable
{
    public function __invoke(Request $request): never
    {
        $route = $request->route();
        $module = $route instanceof Route ? ($route->defaults['_runa_module'] ?? 'unknown') : 'unknown';
        throw new OperationRejected('http_contract_unavailable', is_string($module) ? $module : 'unknown', 'unknown');
    }
}
