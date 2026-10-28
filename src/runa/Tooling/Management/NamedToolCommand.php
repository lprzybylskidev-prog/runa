<?php

namespace Runa\Management;

use InvalidArgumentException;

final class NamedToolCommand extends ToolingCommand
{
    /** @param array{name: string, tool: string, prefix: list<string>, signature: string, description: string} $definition */
    public function __construct(array $definition)
    {
        $this->tool = $definition['tool'];
        $this->prefix = $definition['prefix'];
        $this->signature = $definition['name'].' '.$definition['signature'].' {--plan : Preview without execution} {--host-request= : Internal request binding}';
        $this->description = $definition['description'];
        parent::__construct();
    }

    /** @param list<string> $arguments */
    protected function validateCommandArguments(array $arguments): void
    {
        if ($this->tool === 'host' && ((count($arguments) > 2)
            || ($arguments[0] === 'stop' && count($arguments) !== 1)
            || (isset($arguments[1]) && preg_match('/\A[a-f0-9]{32}\z/D', $arguments[1]) !== 1))) {
            throw new InvalidArgumentException('Provide a valid operation ID to host:status only.');
        }
        if (($this->getName() === 'runa:deploy' && ($arguments[0] ?? '') === 'rollback')
            || ($this->getName() === 'runa:test' && in_array($arguments[0] ?? '', ['frontend', 'e2e'], true))) {
            throw new InvalidArgumentException('Use the dedicated command shown in Artisan list.');
        }
    }
}
