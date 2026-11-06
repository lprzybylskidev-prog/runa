<?php

namespace Runa\Contracts\Modules;

interface ManagementOptionsReader extends ManagementHandler
{
    public function options(ManagementOptionsRead $input): ManagementOptions;
}
