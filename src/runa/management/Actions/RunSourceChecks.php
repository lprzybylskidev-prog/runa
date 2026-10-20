<?php

namespace Runa\Management\Actions;

use InvalidArgumentException;
use Symfony\Component\Console\Output\OutputInterface;

final class RunSourceChecks
{
    /** @param list<string> $arguments */
    public function execute(string $tool, array $arguments, string $root, OutputInterface $output): int
    {
        $runner = new RunTool;
        $cwd = $root.'/src';
        if ($tool === 'format') {
            if (! in_array($arguments, [[], ['--check']], true)) {
                throw new InvalidArgumentException('Format supports only --check.');
            }
            $status = $runner->process(['vendor/bin/pint', ...($arguments ? ['--test'] : [])], $cwd, $output);
            if ($status !== 0) {
                return $status;
            }

            return $runner->process(['pnpm', 'exec', 'prettier', $arguments ? '--check' : '--write', 'app/resources', 'runa/resources', 'runa/tests/frontend', 'runa/tests/e2e', '*.mjs', 'package.json', 'tsconfig.json', 'pint.json', '../.prettierrc.json'], $cwd, $output);
        }
        $commands = ['lint' => ['pnpm', 'exec', 'eslint', 'app/resources', 'runa/resources', 'runa/tests/frontend', 'runa/tests/e2e', '*.mjs', '--max-warnings=0'],
            'typecheck' => ['pnpm', 'run', 'typecheck'], 'analyse' => ['vendor/bin/phpstan', 'analyse', '--memory-limit=1G']];
        if (isset($commands[$tool])) {
            return $runner->process([...$commands[$tool], ...$arguments], $cwd, $output);
        }
        if ($tool !== 'test') {
            throw new InvalidArgumentException('Unsupported source check.');
        }
        $directory = $root.'/.runtime/tests';
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new InvalidArgumentException('Cannot prepare test lock directory.');
        }
        $lock = fopen($directory.'/suite.lock', 'c');
        if ($lock === false || ! flock($lock, LOCK_EX | LOCK_NB)) {
            throw new InvalidArgumentException('Another test suite is running in this checkout.');
        }
        try {
            if (($arguments[0] ?? null) === 'frontend') {
                return $runner->process(['pnpm', 'exec', 'vitest', 'run', ...array_slice($arguments, 1)], $cwd, $output);
            }
            if (($arguments[0] ?? null) === 'e2e') {
                $status = $runner->process([PHP_BINARY, 'runa/resources/errors/build.php'], $cwd, $output);

                return $status !== 0 ? $status : $runner->process(['pnpm', 'exec', 'playwright', 'test', ...array_values(array_filter(array_slice($arguments, 1), static fn (string $argument): bool => $argument !== '--critical'))], $cwd, $output);
            }
            if (is_file($cwd.'/bootstrap/cache/config.php')) {
                throw new InvalidArgumentException('Clear cached development config before testing: php artisan config:clear');
            }
            if (in_array('--parallel', $arguments, true) && ! array_any($arguments, static fn (string $value): bool => str_starts_with($value, '--processes'))) {
                $arguments[] = '--processes='.(getenv('TEST_WORKERS') ?: '4');
            }

            return $runner->process(['vendor/bin/pest', ...$arguments], $cwd, $output, ['APP_ENV' => 'testing']);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
