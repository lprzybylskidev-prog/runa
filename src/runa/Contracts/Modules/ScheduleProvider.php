<?php

namespace Runa\Contracts\Modules;

interface ScheduleProvider
{
    /** @return list<ScheduleDefinition> */
    public function schedules(): array;
}
