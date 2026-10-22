<?php

namespace App\Modules\Reference\Definitions;

use Runa\Contracts\Modules\ScheduleDefinition;
use Runa\Contracts\Modules\ScheduleProvider;

final class ReferenceSchedules implements ScheduleProvider
{
    public function schedules(): array
    {
        return [new ScheduleDefinition('work', 'work', '0 * * * *', 2)];
    }
}
