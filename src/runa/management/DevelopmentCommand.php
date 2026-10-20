<?php

namespace Runa\Management;

final class DevelopmentCommand extends ToolingCommand
{
    protected $signature = 'runa:dev {action} {arguments?*} {--if-needed} {--plan : Print a host plan without execution} {--host-request= : Internal request binding}';

    protected $description = 'Development lifecycle, profiles and setup; execute host operations through the private executor';

    protected string $tool = 'dev';
}
