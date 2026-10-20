<?php

namespace Runa\Management;

final class XdebugCommand extends ToolingCommand
{
    protected $signature = 'runa:xdebug {action} {--plan : Print a host plan without execution} {--host-request= : Internal request binding}';

    protected $description = 'Inspect or toggle development CLI/FPM debugging';

    protected string $tool = 'xdebug';
}
