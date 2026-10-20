<?php

namespace Runa\Management;

final class InstallCommand extends ToolingCommand
{
    protected $signature = 'runa:install {mode} {target} {--simulation} {--plan : Print a host plan without execution} {--host-request= : Internal request binding}';

    protected $description = 'Run the existing production installer; unified dev/prod install follows in point 8';

    protected string $tool = 'install';
}
