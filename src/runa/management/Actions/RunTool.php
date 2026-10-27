<?php

namespace Runa\Management\Actions;

use Illuminate\Console\OutputStyle;
use InvalidArgumentException;
use Runa\Management\DevelopmentContext;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

final class RunTool
{
    /** @param list<string> $arguments */
    public function execute(string $tool, array $arguments, string $root, OutputInterface $output): int
    {
        DevelopmentContext::assertAllowed($root);
        if (in_array($tool, ['test', 'format', 'lint', 'typecheck', 'analyse'], true)) {
            return (new RunSourceChecks)->execute($tool, $arguments, $root, $output);
        }
        if ($tool === 'xdebug') {
            return (new ConfigureXdebug)->execute($arguments[0], $output);
        }
        $doctor = [
            'config' => [PHP_BINARY, 'artisan', 'runa:config:validate'],
            'templates' => [PHP_BINARY, 'artisan', 'runa:config:templates'],
            'hooks' => ['python3', '-m', 'tools.application.testing.hooks'],
            'frontend' => [PHP_BINARY, 'artisan', 'runa:typecheck'],
            'database-migrations' => [PHP_BINARY, 'artisan', 'runa:test-db:guard', '--env=testing'],
            'database' => [PHP_BINARY, 'artisan', 'runa:test', '--scope=backend', '--filter=Database'],
            'seeding' => [PHP_BINARY, 'artisan', 'runa:test', '--scope=backend', '--filter=Seeding'],
        ];
        if ($tool === 'doctor' && isset($doctor[$arguments[0] ?? ''])) {
            return $this->process($doctor[$arguments[0]], $root, $output, $arguments[0] === 'database-migrations' ? ['APP_ENV' => 'testing'] : null);
        }
        if ($tool === 'python') {
            return $this->process(['python3', '-m', 'tools.application.python_cli', ...$arguments], $root, $output);
        }
        if ($tool === 'dev') {
            return $this->process(['python3', '-m', 'tools.application.development', ...$arguments], $root, $output);
        }
        $helpers = ['doctor' => 'tools.application.doctor_cli', 'quality' => 'tools.application.quality_cli',
            'knowledge' => 'tools.application.knowledge.cli', 'security' => 'tools.application.security_cli',
            'check' => 'tools.application.source_checks', 'hook' => 'tools.application.git_hook'];
        if ($tool === 'knowledge' && ! file_exists($root.'/.git/runa-bootstrap.json')
            && ! is_link($root.'/.git/runa-bootstrap.json') && is_dir($root.'/tools/maintainer')
            && is_file($root.'/docs/runa/maintainer/agent-contract.md')) {
            $helpers['knowledge'] = 'tools.maintainer.knowledge_cli';
        }
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
            return $this->process(['python3', '-m', 'tools.application.git_hook', $cwd, ...$arguments], $root, $output);
        }

        return $this->process(['python3', '-m', $helpers[$tool], ...$arguments], $cwd, $output);
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
