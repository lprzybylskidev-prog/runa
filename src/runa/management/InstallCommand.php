<?php

namespace Runa\Management;

use InvalidArgumentException;

final class InstallCommand extends ToolingCommand
{
    protected $signature = 'runa:install {mode} {target} {--settings-file= : Private production JSON settings on the host} {--profile= : Development resource profile} {--resume : Resume this installer after a failed first installation} {--non-interactive : Fail rather than prompt} {--simulation} {--plan : Print a host plan without execution} {--host-request= : Internal request binding}';

    protected $description = 'Install development or production using existing containerized mechanics';

    protected string $tool = 'install';

    /** @param list<string> $arguments */
    protected function validateCommandArguments(array $arguments): void
    {
        if ($arguments[0] === 'dev' && ($this->option('simulation') || $this->option('settings-file'))) {
            throw new InvalidArgumentException('Development install does not accept production settings or simulation.');
        }
        $profile = $this->option('profile');
        if ($profile !== null && ($arguments[0] !== 'dev' || ! in_array($profile, ['low', 'standard', 'high'], true))) {
            throw new InvalidArgumentException('Install profile must be low, standard or high, and is development-only.');
        }
    }
}
