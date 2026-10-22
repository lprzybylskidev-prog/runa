<?php

namespace App\Modules\ModuleProbe\Definitions;

use Runa\Contracts\Modules\JobDefinition;
use Runa\Contracts\Modules\JobProvider;

final readonly class ProbeJobs implements JobProvider
{
    public function jobs(): array
    {
        return [new JobDefinition('probe.work', ProbeJobHandler::class, ProbeJobPolicy::class, 3, 5, 30, true, 'safe_checkpoints_only')];
    }
}
