<?php

namespace App\Modules\ModuleProbe\Definitions;

use Runa\Contracts\Modules\ScheduleDefinition;
use Runa\Contracts\Modules\ScheduleProvider;

final class ProbeSchedules implements ScheduleProvider
{
    public function schedules(): array
    {
        return [new ScheduleDefinition('work', 'work', '0 * * * *', 2)];
    }
}
