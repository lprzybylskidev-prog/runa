<?php

namespace Runa\Management;

use InvalidArgumentException;

final class IntegrityCommand extends ToolingCommand
{
    protected $signature = 'runa:integrity {--json : Emit structured findings} {--plan : Print a host plan without execution} {--host-request= : Internal request binding}';

    protected $description = 'Compare installed protected source with its trusted release; never repair files';

    protected string $tool = 'integrity';

    /** @param list<string> $arguments */
    protected function validateCommandArguments(array $arguments): void
    {
        if (! in_array($arguments, [[], ['--json']], true)) {
            throw new InvalidArgumentException('Integrity accepts only --json; exclusions, repair and baseline refresh are not supported.');
        }
    }
}
