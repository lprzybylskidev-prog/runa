<?php

namespace Runa\Management;

final class DeployCommand extends ToolingCommand
{
    protected $signature = 'runa:deploy {target} {--simulation} {--plan : Print a host plan without execution} {--host-request= : Internal request binding}';

    protected $description = 'Deploy an exact release/SHA or roll back compatible application images';

    protected string $tool = 'deploy';
}
