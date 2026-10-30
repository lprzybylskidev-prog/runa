<?php

namespace Runa\Contracts\Modules;

interface ManagementWidgetReader extends ManagementHandler
{
    public function widget(ManagementRead $input): ManagementWidgetResult;
}
