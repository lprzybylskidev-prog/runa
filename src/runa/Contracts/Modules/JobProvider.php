<?php

namespace Runa\Contracts\Modules;

interface JobProvider
{
    /** @return list<JobDefinition> */
    public function jobs(): array;
}
