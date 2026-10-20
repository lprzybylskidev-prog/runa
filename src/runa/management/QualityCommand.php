<?php

namespace Runa\Management;

final class QualityCommand extends ToolingCommand
{
    protected $signature = 'runa:quality {action} {--background} {--plan : Print a host plan without execution} {--host-request= : Internal request binding}';

    protected $description = 'Run the canonical local quality graph or inspect its latest status';

    protected string $tool = 'quality';
}
