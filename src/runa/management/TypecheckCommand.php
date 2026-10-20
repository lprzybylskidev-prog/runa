<?php

namespace Runa\Management;

final class TypecheckCommand extends ToolingCommand
{
    protected $signature = 'runa:typecheck {arguments?*} {--plan : Print a host plan without execution} {--host-request= : Internal request binding}';

    protected $description = 'Check strict TypeScript contracts';

    protected string $tool = 'typecheck';
}
