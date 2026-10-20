<?php

namespace Runa\Management;

final class SourceCheckCommand extends ToolingCommand
{
    protected $signature = 'runa:check {action} {--plan : Print a host plan without execution} {--host-request= : Internal request binding}';

    protected $description = 'Run source-only quality checks';

    protected string $tool = 'check';
}
