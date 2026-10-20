<?php

namespace Runa\Management;

final class ProductionCommand extends ToolingCommand
{
    protected $signature = 'runa:production {action} {service?} {--plan : Print a host plan without execution} {--host-request= : Internal request binding}';

    protected $description = 'Validate/build/inspect or manage production Compose on the host';

    protected string $tool = 'production';
}
