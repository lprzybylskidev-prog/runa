<?php

namespace Runa\Contracts\Modules;

interface ManagementResourceReader extends ManagementHandler
{
    public function page(ManagementRead $input): ManagementPageResult;

    public function read(ManagementRead $input): ManagementRecord;
}
