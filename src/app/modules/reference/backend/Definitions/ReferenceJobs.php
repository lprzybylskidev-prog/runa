<?php

namespace App\Modules\Reference\Definitions;

use Runa\Contracts\Modules\JobDefinition;
use Runa\Contracts\Modules\JobProvider;

final readonly class ReferenceJobs implements JobProvider
{
    public function jobs(): array
    {
        return [new JobDefinition('reference.work', ReferenceJobHandler::class, ReferenceJobPolicy::class, 3, 5, 30, true, 'safe_checkpoints_only'), new JobDefinition('reference.event', ReferenceEventHandler::class, ReferenceJobPolicy::class, 3, 5, 30, true, 'effect_free_subscriber')];
    }
}
