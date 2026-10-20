<?php

namespace Runa\Management;

final class FormatCommand extends ToolingCommand
{
    protected $signature = 'runa:format {--check} {--plan : Print a host plan without execution} {--host-request= : Internal request binding}';

    protected $description = 'Format application sources or verify formatting';

    protected string $tool = 'format';
}
