<?php

namespace App\Modules\ModuleProbe\Definitions;

use Runa\Contracts\Modules\WorkHandler;
use Runa\Support\Modules\Events\ModuleEvents;
use Runa\Support\Modules\Work\WorkContext;

final readonly class ProbeJobHandler implements WorkHandler
{
    public function handle(WorkContext $context, array $payload): void
    {
        // No business effect or durable module data. Demonstrate a cooperative safe boundary.
        $context->checkpoint('probe_completed');
        app(ModuleEvents::class)->dispatch('app.module_probe', 'observed', 1, ['work_id' => $context->workId]);
    }
}
