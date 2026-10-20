<?php

namespace Runa\Management;

final class ProjectCommand extends ToolingCommand
{
    protected $signature = 'runa:project {action} {--evidence=} {--plan : Print a host plan without execution} {--host-request= : Internal request binding}';

    protected $description = 'Record validated first real production deployment evidence';

    protected string $tool = 'project';
}
