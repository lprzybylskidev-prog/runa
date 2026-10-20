<?php

namespace Runa\Management\Actions;

use Illuminate\Console\OutputStyle;
use InvalidArgumentException;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

final class RunTool
{
    /** @param list<string> $arguments */
    public function execute(string $tool, array $arguments, string $root, OutputInterface $output): int
    {
        if (in_array($tool, ['test', 'format', 'lint', 'typecheck', 'analyse'], true)) {
            return (new RunSourceChecks)->execute($tool, $arguments, $root, $output);
        }
        if ($tool === 'xdebug') {
            return (new ConfigureXdebug)->execute($arguments[0], $output);
        }
        $doctor = [
            'config' => [PHP_BINARY, 'artisan', 'runa:config:validate', '--templates'],
            'hooks' => ['python3', 'tools/testing/hooks.py'],
            'frontend' => ['node', 'src/runa/tools/testing/frontend.mjs'],
            'database-migrations' => ['python3', 'tools/testing/database-guards.py'],
            'database' => [PHP_BINARY, 'artisan', 'runa:test', '--filter=Database'],
            'seeding' => [PHP_BINARY, 'artisan', 'runa:test', '--filter=Seeding'],
        ];
        if ($tool === 'doctor' && isset($doctor[$arguments[0] ?? ''])) {
            return $this->process($doctor[$arguments[0]], $root, $output);
        }
        $helpers = ['dev' => 'dev', 'doctor' => 'doctor', 'quality' => 'quality', 'knowledge' => 'knowledge',
            'security' => 'security', 'check' => 'quality_check', 'hook' => 'git_hook'];
        if (! isset($helpers[$tool])) {
            throw new InvalidArgumentException('Unsupported internal tool executor.');
        }

        $cwd = $root;
        if ($tool === 'hook') {
            $filtered = [];
            foreach ($arguments as $argument) {
                if (str_starts_with($argument, '--repository=')) {
                    $repository = realpath(substr($argument, strlen('--repository=')));
                    if ($repository === false || ! is_dir($repository)) {
                        throw new InvalidArgumentException('Invalid hook repository context.');
                    }
                    $cwd = $repository;
                } else {
                    $filtered[] = $argument;
                }
            }
            $arguments = $filtered;
        }

        if ($tool === 'hook') {
            return $this->process(['python3', $root.'/distribution/maintenance/git_hook.py', ...$arguments], $cwd, $output);
        }

        return $this->process(['python3', $root.'/tools/project/executors/'.$helpers[$tool].'.py', ...$arguments], $cwd, $output);
    }

    /** @param list<string> $command
     * @param  array<string, string>|null  $environment
     */
    public function process(array $command, string $cwd, OutputInterface $output, ?array $environment = null): int
    {
        $process = new Process($command, $cwd, $environment);
        $process->setTimeout(null);

        return $process->run(static function (string $type, string $buffer) use ($output): void {
            $native = $output instanceof OutputStyle ? $output->getOutput() : $output;
            $stream = $type === Process::ERR && $native instanceof ConsoleOutputInterface ? $native->getErrorOutput() : $native;
            $stream->write($buffer, false, OutputInterface::OUTPUT_RAW);
        });
    }
}
