<?php

namespace Runa\Testing\Backend;

use Illuminate\Console\Command;
use NunoMaduro\Collision\Adapters\Laravel\Commands\TestCommand;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessSignaledException;
use Symfony\Component\Process\Process;

/** Native engine transports share TestRun; Collision is composed under its final contract. */
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
        $rawArguments = $_SERVER['argv'] ?? null;
        if (! is_array($rawArguments) || ! array_is_list($rawArguments)) {
            throw new RuntimeException('Native testing requires literal CLI arguments.');
        }
        $arguments = [];
        foreach ($rawArguments as $argument) {
            if (! is_string($argument)) {
                throw new RuntimeException('Native testing requires literal CLI arguments.');
            }
            $arguments[] = $argument;
        }
        if (function_exists('Pest\\version') && $this->option('parallel')) {
            if ($this->input->hasParameterOption(['--list-tests', '--list-tests-xml', '--list-suites', '--list-groups'])) {
                return $this->parallelPest($arguments, discovery: true);
            }

            return TestRun::execute(dirname(base_path()), $this->output, fn (): int => $this->parallelPest($arguments));
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

    /** @param list<string> $arguments */
    private function parallelPest(array $arguments, bool $discovery = false): int
    {
        // Pest consumes its root option before ParaTest. Collision validates
        // ParaTest arguments earlier, so its parallel adapter cannot carry it.
        $options = [];
        $skipValue = false;
        foreach (array_slice($arguments, 2) as $argument) {
            if ($skipValue) {
                $skipValue = false;

                continue;
            }
            if ($discovery && $argument === '--processes') {
                $skipValue = true;

                continue;
            }
            if ($discovery && (in_array($argument, ['--parallel', '-p', '--recreate-databases', '--drop-databases'], true) || str_starts_with($argument, '--processes='))) {
                continue;
            }
            if ($argument === '--env') {
                $skipValue = true;

                continue;
            }
            if (str_starts_with($argument, '--env=') || in_array($argument, ['--without-tty', '--without-databases', '--without-cache'], true)) {
                continue;
            }
            $options[] = $argument;
        }
        $environment = TestRun::testingEnvironment(dirname(base_path()));
        $environment['LARAVEL_PARALLEL_TESTING'] = $discovery ? false : '1';
        foreach (['recreate-databases', 'drop-databases', 'without-databases', 'without-cache'] as $option) {
            $environment['LARAVEL_PARALLEL_TESTING_'.strtoupper(str_replace('-', '_', $option))] = ! $discovery && $this->option($option) ? '1' : false;
        }
        $process = new Process([PHP_BINARY, 'vendor/bin/pest', ...$options, '--test-directory=../tests/backend/app'], base_path(), $environment);
        $process->setTimeout(null);
        $process->setTty(! $this->option('without-tty') && Process::isTtySupported());
        try {
            return $process->run(fn (string $type, string $buffer) => $this->output->write($buffer, false, OutputInterface::OUTPUT_RAW));
        } catch (ProcessSignaledException $error) {
            if ($error->getSignal() !== SIGINT) {
                throw $error;
            }

            return 130;
        }
    }
}
