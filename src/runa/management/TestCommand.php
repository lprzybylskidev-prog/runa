<?php

namespace Runa\Management;

final class TestCommand extends ToolingCommand
{
    protected $signature = 'runa:test {arguments?*} {--parallel} {--processes=} {--filter=} {--compact} {--critical} {--plan : Print a host plan without execution} {--host-request= : Internal request binding}';

    protected $description = 'Run backend (default), frontend or e2e tests; runner options may follow --';

    protected string $tool = 'test';
}
