<?php

namespace Runa\Testing\Backend;

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidFileException;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/** Shared application/native test lock and disposable worker lifecycle. */
final class TestRun
{
    /** @param callable(): int $run */
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

    /** @return array<string, string|false> */
    public static function testingEnvironment(string $root): array
    {
        // The parent Artisan kernel may already have loaded development .env values.
        // Clear only that file's keys in this child, then load the guarded testing identity.
        $environment = [];
        $file = $root.'/.env';
        if (is_file($file)) {
            $contents = file_get_contents($file);
            if ($contents === false) {
                throw new RuntimeException('Cannot read application environment for test cleanup.');
            }
            try {
                $keys = array_keys(Dotenv::parse($contents));
            } catch (InvalidFileException) {
                throw new RuntimeException('Invalid application environment for test cleanup.');
            }
            foreach ($keys as $key) {
                $environment[$key] = false;
            }
        }
        $environment['APP_ENV'] = 'testing';
        $environment['TEST_TOKEN'] = false;

        return $environment;
    }

    private static function cleanup(string $root, OutputInterface $output): int
    {
        $process = new Process([PHP_BINARY, 'runa/testing/backend/worker-databases.php'], $root.'/src', self::testingEnvironment($root));
        $process->setTimeout(null);

        return $process->run(static fn (string $type, string $buffer) => $output->write($buffer, false, OutputInterface::OUTPUT_RAW));
    }
}
