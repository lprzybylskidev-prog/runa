<?php

namespace Runa\Management;

final class SecurityCommand extends ToolingCommand
{
    protected $signature = 'runa:security {action} {--dependencies-root=} {--plan : Print a host plan without execution} {--host-request= : Internal request binding}';

    protected $description = 'Run dependency, secret, image or changed-source review checks';

    protected string $tool = 'security';
}
