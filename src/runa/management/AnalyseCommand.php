<?php

namespace Runa\Management;

final class AnalyseCommand extends ToolingCommand
{
    protected $signature = 'runa:analyse {arguments?*} {--plan : Print a host plan without execution} {--host-request= : Internal request binding}';

    protected $description = 'Run maximum-level PHP static analysis';

    protected string $tool = 'analyse';
}
