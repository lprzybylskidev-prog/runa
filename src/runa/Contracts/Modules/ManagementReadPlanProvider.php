<?php

namespace Runa\Contracts\Modules;

/** Pure input-based source planning; no business handler, IO or mutation. */
interface ManagementReadPlanProvider extends ManagementPageProvider
{
    /** @return list<ManagementReadCall> */
    public function readPlan(string $binding, string $capability, ManagementRead|ManagementOptionsRead $input): array;
}
