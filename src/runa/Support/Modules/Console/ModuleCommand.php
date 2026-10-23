<?php

namespace Runa\Support\Modules\Console;

use Illuminate\Console\Command;
use Throwable;

final class ModuleCommand extends Command
{
    public function __construct(private readonly ModuleConsole $modules, private readonly string $module, private readonly string $key)
    {
        $this->signature = str_replace('.', ':', $module).':'.$key.' {--payload= : Bounded JSON object or array}';
        $this->description = 'Execute the source-classified module command.';
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $json = $this->option('payload') ?? '{}';
            if (strlen($json) > 65536) {
                throw new \RuntimeException('Invalid payload.');
            }
            $payload = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
            if (! is_array($payload)) {
                throw new \RuntimeException('Invalid payload.');
            }
            $this->line(json_encode($this->modules->execute($this->module, $this->key, $payload), JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        } catch (Throwable) {
            $this->error('Module command unavailable or invalid. Inspect module diagnostics.');

            return self::FAILURE;
        }
    }
}
