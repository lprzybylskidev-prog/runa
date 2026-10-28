<?php

namespace Runa\Testing\Backend;

use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/** Shared application/native test lock and disposable worker lifecycle. */
final class TestRun
{
    public static function execute(string $root, OutputInterface $output, callable $run, bool $databases = true): int
    {
        if (is_file($root.'/src/bootstrap/cache/config.php')) {
            throw new RuntimeException('Clear cached development config before testing: php artisan config:clear');
        }
        $directory = $root.'/.runtime/tests';
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException('Cannot prepare test lock directory.');
        }
        $lock = fopen($directory.'/suite.lock', 'c');
        if ($lock === false || ! flock($lock, LOCK_EX | LOCK_NB)) {
            throw new RuntimeException('Another test suite is running in this checkout.');
        }
        $handlers = [];
        if (function_exists('pcntl_signal')) {
            foreach ([SIGINT, SIGTERM] as $signal) {
                $handlers[$signal] = pcntl_signal_get_handler($signal);
                pcntl_signal($signal, static function (int $signal): never {
                    throw new RuntimeException('Test run interrupted by signal '.$signal);
                });
            }
            $async = pcntl_async_signals(true);
        }
        try {
            $status = $databases ? self::cleanup($root, $output) : 0;
            if ($status !== 0) {
                return $status;
            }
            try {
                $status = $run();
            } finally {
                $cleanup = $databases ? self::cleanup($root, $output) : 0;
            }

            return $status !== 0 ? $status : $cleanup;
        } finally {
            foreach ($handlers as $signal => $handler) {
                pcntl_signal($signal, $handler);
            }
            if (isset($async)) {
                pcntl_async_signals($async);
            }
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private static function cleanup(string $root, OutputInterface $output): int
    {
        $process = new Process([PHP_BINARY, 'runa/testing/backend/worker-databases.php'], $root.'/src', [
            'APP_ENV' => 'testing', 'TEST_TOKEN' => false,
        ]);
        $process->setTimeout(null);

        return $process->run(static fn (string $type, string $buffer) => $output->write($buffer, false, OutputInterface::OUTPUT_RAW));
    }
}
