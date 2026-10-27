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
        $sourcePaths = array_values(array_filter(['app/resources', 'runa/resources', 'app/tests/frontend', 'app/tests/e2e', 'runa/tests/frontend', 'runa/tests/e2e'], static fn (string $path): bool => is_dir($cwd.'/'.$path)));
        if ($tool === 'format') {
            if (! in_array($arguments, [[], ['--check']], true)) {
                throw new InvalidArgumentException('Format supports only --check.');
            }
            $status = $runner->process(['vendor/bin/pint', ...($arguments ? ['--test'] : [])], $cwd, $output);
            if ($status !== 0) {
                return $status;
            }

            return $runner->process(['pnpm', 'exec', 'prettier', $arguments ? '--check' : '--write', ...$sourcePaths, '*.mjs', 'package.json', 'tsconfig.json', 'pint.json', '../.prettierrc.json'], $cwd, $output);
        }
        $commands = ['lint' => ['pnpm', 'exec', 'eslint', ...$sourcePaths, '*.mjs', '--max-warnings=0'],
            'typecheck' => ['pnpm', 'run', 'typecheck'], 'analyse' => ['vendor/bin/phpstan', 'analyse', '--memory-limit=1G']];
        if (isset($commands[$tool])) {
            return $runner->process([...$commands[$tool], ...$arguments], $cwd, $output);
        }
        if ($tool !== 'test') {
            throw new InvalidArgumentException('Unsupported source check.');
        }

        return $runner->process(['python3', '-m', 'tools.application.testing.cli', ...$arguments], $root, $output);
    }
}
