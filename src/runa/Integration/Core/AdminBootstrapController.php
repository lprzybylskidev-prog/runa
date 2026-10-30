<?php

namespace Runa\Integration\Core;

use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Runa\Contracts\Modules\ApiOperation;
use Runa\ModuleFramework\Api\ModuleDeclarations;
use Runa\ModuleFramework\Api\OperationGuard;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\Support\Modules\Api\OperationRejected;

/** Lazy fixed dispatcher: a cold/retained registry must not load module code at route boot. */
final readonly class AdminBootstrapController
{
    public function __construct(private Container $container, private ModuleRegistry $registry,
        private ModuleDeclarations $declarations, private OperationGuard $guard) {}

    public function __invoke(Request $request): mixed
    {
        $context = $this->guard->enter('runa.admin', new ApiOperation('bootstrap', 'runa.admin.enter'));
        $record = $this->registry->snapshot()->modules['runa.admin']
            ?? throw new OperationRejected('module_unavailable', 'runa.admin', 'bootstrap');
        $controller = $this->declarations->make($record, 'Runa\\Modules\\Admin\\Http\\Controllers\\BootstrapController');
        $this->guard->assertCurrent($context);

        return $this->container->call([$controller, '__invoke']);
    }
}
