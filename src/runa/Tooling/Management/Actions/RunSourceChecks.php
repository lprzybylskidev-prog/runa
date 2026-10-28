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
        $sourcePaths = array_values(array_filter(['app/resources', 'runa/resources', 'runa/Testing', 'app/modules', 'runa/modules', 'runa/frontend', 'runa/build', '../tests/frontend/app', '../tests/frontend/runa'], static fn (string $path): bool => is_dir($cwd.'/'.$path)));
        if ($tool === 'format') {
            if (! in_array($arguments, [[], ['--check']], true)) {
                throw new InvalidArgumentException('Format supports only --check.');
            }
            $phpPaths = array_values(array_filter(['.', '../tests/backend/app', '../tests/backend/runa'], static fn (string $path): bool => is_dir($cwd.'/'.$path)));
            $status = $runner->process(['vendor/bin/pint', ...($arguments ? ['--test'] : []), ...$phpPaths], $cwd, $output);
            if ($status !== 0) {
                return $status;
            }

            return $runner->process(['pnpm', 'exec', 'prettier', $arguments ? '--check' : '--write', ...$sourcePaths, '*.mjs', 'package.json', 'tsconfig.json', 'pint.json', '../.prettierrc.json'], $cwd, $output);
        }
        if ($tool === 'lint') {
            return $runner->process(['node', 'src/node_modules/eslint/bin/eslint.js', '--config', 'eslint.config.mjs', ...array_map(static fn (string $path): string => 'src/'.$path, $sourcePaths), 'src/*.mjs', '--max-warnings=0', ...$arguments], $root, $output);
        }
        $commands = [
            'typecheck' => ['pnpm', 'run', 'typecheck'], 'analyse' => ['vendor/bin/phpstan', 'analyse', '--memory-limit=1G']];
        if ($tool === 'analyse') {
            $status = $runner->process([PHP_BINARY, 'runa/Development/check-api.php'], $cwd, $output);
            if ($status !== 0) {
                return $status;
            }
        }
        if (isset($commands[$tool])) {
            return $runner->process([...$commands[$tool], ...$arguments], $cwd, $output);
        }
        if ($tool !== 'test') {
            throw new InvalidArgumentException('Unsupported source check.');
        }

        return $runner->process(['python3', '-m', 'tools.application.testing.cli', ...$arguments], $root, $output);
    }
}
