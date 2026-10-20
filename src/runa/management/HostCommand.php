<?php

namespace Runa\Management;

use InvalidArgumentException;

final class HostCommand extends ToolingCommand
{
    protected $signature = 'runa:host {action} {request? : Operation ID after a disconnected command} {--plan : Preview without execution} {--host-request= : Internal request binding}';

    protected $description = 'Inspect or stop the private host executor; recover it with the host transport';

    protected string $tool = 'host';

    /** @param list<string> $arguments */
    protected function validateCommandArguments(array $arguments): void
    {
        if (! in_array($arguments[0], ['status', 'stop'], true)
            || ($arguments[0] === 'stop' && count($arguments) !== 1)
            || (isset($arguments[1]) && preg_match('/\A[a-f0-9]{32}\z/D', $arguments[1]) !== 1)) {
            throw new InvalidArgumentException('Host control accepts status [operation-id] or stop.');
        }
    }
}
