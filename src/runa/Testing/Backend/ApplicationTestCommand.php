<?php

namespace Runa\Testing\Backend;

use Illuminate\Console\Command;
use RuntimeException;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessSignaledException;
use Symfony\Component\Process\Process;

/** Owns the native Pest CLI transport; Pest owns PHPUnit and parallel execution. */
final class ApplicationTestCommand extends Command
{
    protected $signature = 'test
        {--without-tty : Disable output to TTY}
        {--compact : Use the native compact printer}
        {--coverage : Collect native code coverage}
        {--min= : Minimum native coverage percentage}
        {--p|parallel : Run native tests in parallel}
        {--profile : List the slowest native tests}
        {--recreate-databases : Recreate parallel test databases}
        {--drop-databases : Drop parallel test databases after execution}
        {--without-databases : Skip Laravel parallel database configuration}
        {--without-cache : Skip Laravel parallel cache configuration}';

    protected $description = 'Run the application PHP tests with Pest';

    public function __construct()
    {
        parent::__construct();
        $this->ignoreValidationErrors();
    }

    public function handle(): int
    {
        if (! $this->input instanceof ArgvInput) {
            throw new RuntimeException('Native testing requires literal CLI arguments.');
        }
        $arguments = $this->input->getRawTokens(true);
        $discovery = $this->input->hasParameterOption(['--list-tests', '--list-tests-xml', '--list-suites', '--list-groups'], true);
        $parallel = ! $discovery && $this->input->hasParameterOption(['--parallel', '-p'], true);
        $withoutTty = $this->input->hasParameterOption('--without-tty', true);
        $options = [];
        $skipValue = false;
        $literalTail = false;
        foreach ($arguments as $argument) {
            if ($literalTail || $argument === '--') {
                $literalTail = true;
                $options[] = $argument;

                continue;
            }
            if ($skipValue) {
                $skipValue = false;

                continue;
            }
            if ($argument === '--env' || ($discovery && $argument === '--processes')) {
                $skipValue = true;

                continue;
            }
            if (str_starts_with($argument, '--env=') || in_array($argument, ['--without-tty', '--without-databases', '--without-cache', '--no-interaction', '-n', '--quiet', '-q', '--verbose', '-v', '-vv', '-vvv'], true)) {
                continue;
            }
            if ($discovery && (in_array($argument, ['--parallel', '-p', '--recreate-databases', '--drop-databases'], true) || str_starts_with($argument, '--processes='))) {
                continue;
            }
            $options[] = match ($argument) {
                '--ansi' => '--colors=always',
                '--no-ansi' => '--colors=never',
                default => $argument,
            };
        }
        $base = $this->laravel->basePath();
        $root = dirname($base);
        $environment = TestRun::testingEnvironment($root);
        $environment['LARAVEL_PARALLEL_TESTING'] = $parallel ? '1' : false;
        foreach (['recreate-databases', 'drop-databases', 'without-databases', 'without-cache'] as $option) {
            $environment['LARAVEL_PARALLEL_TESTING_'.strtoupper(str_replace('-', '_', $option))] = $parallel && $this->input->hasParameterOption('--'.$option, true) ? '1' : false;
        }
        $configuration = $this->input->hasParameterOption(['--configuration', '-c', '--no-configuration'], true)
            ? [] : ['--configuration='.(is_file($base.'/phpunit.xml') ? 'phpunit.xml' : 'phpunit.xml.dist')];
        $binary = PHP_SAPI === 'phpdbg' ? [PHP_BINARY, '-qrr'] : [PHP_BINARY];
        $process = new Process([...$binary, 'vendor/bin/pest', ...$configuration, '--test-directory=../tests/backend/app', ...$options], $base, $environment);
        $process->setTimeout(null);
        $process->setTty(! $withoutTty && Process::isTtySupported());
        if (! $process->isTty() && defined('STDIN')) {
            $process->setInput(STDIN);
        }
        $run = fn (): int => $this->launch($process);

        return $discovery ? $run() : TestRun::execute($root, $this->output, $run);
    }

    private function launch(Process $process): int
    {
        $handlers = [];
        $interrupted = null;
        if (function_exists('pcntl_signal')) {
            foreach ([SIGINT, SIGTERM] as $signal) {
                $handlers[$signal] = pcntl_signal_get_handler($signal);
                pcntl_signal($signal, static function (int $signal) use ($process, &$interrupted): void {
                    $interrupted = $signal;
                    if ($process->isRunning()) {
                        $process->signal($signal);
                    }
                });
            }
            $async = pcntl_async_signals(true);
        }
        try {
            try {
                $status = $process->run(fn (string $type, string $buffer) => $this->output->write($buffer, false, OutputInterface::OUTPUT_RAW));
            } catch (ProcessSignaledException $error) {
                $status = 128 + $error->getSignal();
            }

            return $interrupted === null ? $status : 128 + $interrupted;
        } finally {
            foreach ($handlers as $signal => $handler) {
                pcntl_signal($signal, $handler);
            }
            if (isset($async)) {
                pcntl_async_signals($async);
            }
        }
    }
}
