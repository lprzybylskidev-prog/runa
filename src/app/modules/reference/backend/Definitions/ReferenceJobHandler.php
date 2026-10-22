<?php

namespace App\Modules\Reference\Definitions;

use Illuminate\Support\Facades\Log;
use Runa\Contracts\Modules\WorkHandler;
use Runa\Support\Modules\Events\ModuleEvents;
use Runa\Support\Modules\Storage\ModuleStorage;
use Runa\Support\Modules\Work\WorkContext;

final readonly class ReferenceJobHandler implements WorkHandler
{
    public function handle(WorkContext $context, array $payload): void
    {
        // Repeat-safe local output; a checkpoint precedes every externally visible effect.
        $context->checkpoint('reference_before_output');
        app(ModuleStorage::class)->disk('app.reference')->put('last-observation.txt', 'Reference work completed.');
        Log::info('Reference work observed.', ['code' => 'reference_work']);
        $context->checkpoint('reference_completed');
        app(ModuleEvents::class)->dispatch('app.reference', 'observed', 1, ['work_id' => $context->workId]);
    }
}
