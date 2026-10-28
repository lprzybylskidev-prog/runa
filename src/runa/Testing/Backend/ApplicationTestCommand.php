<?php

namespace Runa\Testing\Backend;

use Illuminate\Console\Command;
use NunoMaduro\Collision\Adapters\Laravel\Commands\TestCommand;
use RuntimeException;

/** Preserve the native CLI through composition; Collision's command is final by contract. */
final class ApplicationTestCommand extends Command
{
    public function __construct(private readonly TestCommand $native)
    {
        parent::__construct();
        $this->setName('test');
        $this->setDescription($native->getDescription());
        $this->setDefinition(clone $native->getDefinition());
        $this->ignoreValidationErrors();
    }

    public function handle(): int
    {
        $this->native->setLaravel($this->laravel);
        $this->native->setApplication($this->getApplication());
        $arguments = $_SERVER['argv'] ?? null;
        if (! is_array($arguments) || ! array_is_list($arguments) || ! array_all($arguments, static fn (mixed $value): bool => is_string($value))) {
            throw new RuntimeException('Native testing requires literal CLI arguments.');
        }
        // Collision reads raw CLI arguments. Scope this transport adaptation to its call.
        // Keep the native runner, flags, terminal streams and original argv after every exit.
        try {
            if (function_exists('Pest\\version')) {
                $_SERVER['argv'] = [...$arguments, '--test-directory=../tests/backend/app'];
            }
            $run = fn (): int => $this->native->run($this->input, $this->output);
            if ($this->input->hasParameterOption(['--list-tests', '--list-tests-xml', '--list-suites', '--list-groups'])) {
                return $run();
            }

            return TestRun::execute(dirname(base_path()), $this->output, $run);
        } finally {
            $_SERVER['argv'] = $arguments;
        }
    }
}
