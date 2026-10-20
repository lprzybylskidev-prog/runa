<?php

namespace Runa\Management;

final class LintCommand extends ToolingCommand
{
    protected $signature = 'runa:lint {arguments?*} {--plan : Print a host plan without execution} {--host-request= : Internal request binding}';

    protected $description = 'Run zero-warning JavaScript, TypeScript and Vue linting';

    protected string $tool = 'lint';
}
