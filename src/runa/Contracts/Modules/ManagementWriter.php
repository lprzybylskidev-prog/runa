<?php

namespace Runa\Contracts\Modules;

interface ManagementWriter extends ManagementHandler
{
    public function preview(ManagementWrite $input): ManagementReview;

    public function execute(ManagementWrite $input): ManagementOutcome;
}
