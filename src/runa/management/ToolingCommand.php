<?php

namespace Runa\Management;

use Illuminate\Console\Command;
use InvalidArgumentException;
use Runa\Management\Actions\RunTool;
use Symfony\Component\Console\Input\ArgvInput;

abstract class ToolingCommand extends Command
{
    protected string $tool;

    private const ACTIONS = [
        'dev' => ['prepare', 'up', 'down', 'config', 'shell', 'profile', 'embeddings', 'build', 'recreate', 'restart', 'logs', 'status', 'setup', 'doctor', 'knowledge-init', 'knowledge-status'],
        'doctor' => ['repository', 'config', 'maintenance', 'hooks', 'frontend', 'database-migrations', 'database', 'seeding', 'boost', 'tooling', 'docs', 'release', 'cleanliness', 'development', 'production'],
        'quality' => ['show', 'roadmap', 'final', 'status'],
        'knowledge' => ['search', 'index', 'status', 'rebuild', 'stale', 'doctor', 'eval', 'initialize'],
        'security' => ['trivy', 'review-changed-surface', 'dependencies', 'secrets'],
        'project' => ['mark-production-deployed'], 'xdebug' => ['on', 'off', 'status'],
        'check' => ['debug-residue'], 'hook' => ['pre-commit', 'commit-msg'],
        'production' => ['validate', 'build', 'up', 'down', 'restart', 'status', 'logs', 'self-test', 'verify-ai-isolation'],
        'backup' => ['create', 'verify', 'restore'], 'install' => ['dev', 'prod', 'production'], 'host' => ['status', 'stop'],
    ];

    public function __construct()
    {
        parent::__construct();
        if (isset(self::ACTIONS[$this->tool])) {
            $this->setHelp('Accepted action/area: '.implode(', ', self::ACTIONS[$this->tool]).'.');
        }
        if (in_array($this->tool, ['test', 'lint', 'typecheck', 'analyse'], true)) {
            $this->ignoreValidationErrors();
        }
    }

    public function handle(): int
    {
        try {
            $arguments = $this->forwardedArguments();
            $this->validateArguments($arguments);
            $this->validateCommandArguments($arguments);
            $request = $this->input->getParameterOption('--host-request', null);
            if ($request !== null && (! is_string($request) || preg_match('/\A[a-f0-9]{32}\z/D', $request) !== 1)) {
                throw new InvalidArgumentException('Invalid host request identity.');
            }
            $operation = 'tooling.'.$this->tool;
            $parameters = ['arguments' => $arguments];
            if ($this->tool === 'dev') {
                $operation = 'development.'.$arguments[0];
                $parameters = count($arguments) === 1 ? new \stdClass : ['arguments' => array_slice($arguments, 1)];
            }
            $host = in_array($this->tool, ['production', 'deploy', 'backup', 'install', 'host'], true)
                || ($this->tool === 'quality' && in_array($arguments[0], ['roadmap', 'final'], true))
                || ($this->tool === 'security' && $arguments[0] === 'trivy')
                || ($this->tool === 'doctor' && ($arguments[0] ?? null) === 'production')
                || ($this->tool === 'dev' && ! in_array($arguments[0], ['setup', 'doctor', 'knowledge-init', 'knowledge-status'], true) && ! ($arguments[0] === 'profile' && count($arguments) === 1));
            $root = dirname(__DIR__, 3);
            if (! $this->input->hasParameterOption('--plan') && $request === null && ! $host && is_file('/.dockerenv') && is_file($root.'/bin/dev')) {
                if (getenv('APP_ENV') === 'production') {
                    throw new InvalidArgumentException('Development tooling is unavailable in production.');
                }

                return (new RunTool)->execute($this->tool, $arguments, $root, $this->output);
            }
            if ($host && ! $this->input->hasParameterOption('--plan') && $request === null) {
                return (new HostBridge)->execute($root, 'runa:'.$this->tool, $arguments, $this->input->isInteractive() && defined('STDIN') && stream_isatty(STDIN), $this->output);
            }
            // Explicit preview or isolated host registry validation only.
            $this->line(json_encode(['schema_version' => 1, 'request_id' => $request,
                'operation' => $operation, 'parameters' => $parameters], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        } catch (InvalidArgumentException $error) {
            $this->error($error->getMessage());

            return self::INVALID;
        }
    }

    /** @param list<string> $arguments */
    protected function validateCommandArguments(array $arguments): void {}

    /** @return list<string> */
    private function forwardedArguments(): array
    {
        if ($this->input instanceof ArgvInput) {
            $result = [];
            $skip = false;
            foreach ($this->input->getRawTokens(true) as $argument) {
                if ($skip) {
                    $skip = false;

                    continue;
                }
                if ($argument === '--host-request') {
                    $skip = true;

                    continue;
                }
                if (str_starts_with($argument, '--host-request=') || in_array($argument, ['--plan', '--no-interaction', '-n', '--no-ansi', '--ansi', '--quiet', '-q'], true)) {
                    continue;
                }
                if ($argument !== '--') {
                    $result[] = $argument;
                }
            }

            return $result;
        }
        $result = [];
        foreach ($this->getDefinition()->getArguments() as $key => $definition) {
            $value = $this->argument($key);
            if ($key !== 'command' && $value !== null) {
                foreach (is_array($value) ? $value : [$value] as $argument) {
                    if (! is_string($argument)) {
                        throw new InvalidArgumentException('Command arguments must be strings.');
                    }
                    $result[] = $argument;
                }
            }
        }
        foreach ($this->options() as $name => $value) {
            if (in_array($name, ['plan', 'host-request', 'help', 'quiet', 'verbose', 'version', 'ansi', 'no-interaction'], true)) {
                continue;
            }
            if ($value === true) {
                $result[] = '--'.$name;
            } elseif (is_string($value)) {
                $result[] = '--'.$name.'='.$value;
            }
        }

        return $result;
    }

    /** @param list<string> $arguments */
    private function validateArguments(array $arguments): void
    {

        if (isset(self::ACTIONS[$this->tool]) && ! ($this->tool === 'doctor' && $arguments === []) && ! in_array($arguments[0] ?? null, self::ACTIONS[$this->tool], true)) {
            throw new InvalidArgumentException('Unsupported action. Use php artisan help runa:'.$this->tool.'.');
        }
        if (count($arguments) > 128 || array_any($arguments, static fn (string $value): bool => strlen($value) > 4096 || str_contains($value, "\0"))) {
            throw new InvalidArgumentException('Command arguments exceed transport bounds.');
        }
        if ($this->tool === 'quality' && in_array($arguments[0], ['show', 'status'], true) && count($arguments) !== 1) {
            throw new InvalidArgumentException('Quality show/status take no execution options.');
        }
        if ($this->tool === 'deploy' || $this->tool === 'install') {
            $target = $arguments[$this->tool === 'install' ? 1 : 0] ?? '';
            if (! ($this->tool === 'deploy' && $target === 'rollback') && preg_match('/\A(?:v(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)|[a-f0-9]{40})\z/D', $target) !== 1) {
                throw new InvalidArgumentException('An exact vX.Y.Z release or full SHA is required.');
            }
        }
        if ($this->tool === 'backup' && (($arguments[0] === 'create') !== ! isset($arguments[1]))) {
            // Options are not a backup ID; the executor validates confirmation and backup provenance too.
            if ($arguments[0] !== 'create' || isset($arguments[1])) {
                throw new InvalidArgumentException('Backup create takes no ID; verify/restore require an ID.');
            }
        }

        if ($this->tool === 'dev') {
            $action = $arguments[0];
            $tail = array_slice($arguments, 1);
            $services = ['app', 'nginx', 'postgres', 'queue', 'scheduler', 'mailpit', 'qdrant', 'embeddings', 'embeddings-gpu', 'setup', 'knowledge-init'];
            if (in_array($action, ['prepare', 'up', 'down', 'config', 'shell', 'status', 'doctor', 'knowledge-init', 'knowledge-status'], true) && $tail !== []) {
                throw new InvalidArgumentException('This development action takes no additional arguments.');
            }
            if ($action === 'setup' && ! in_array($tail, [[], ['--if-needed']], true)) {
                throw new InvalidArgumentException('Setup supports only --if-needed.');
            }
            if (in_array($action, ['restart', 'logs'], true) && (count($tail) > 1 || ($tail !== [] && ! in_array($tail[0], $services, true)))) {
                throw new InvalidArgumentException('Provide at most one known development service.');
            }
            if (in_array($action, ['build', 'recreate'], true)) {
                $allowed = $action === 'build' ? array_diff($services, ['nginx', 'postgres']) : $services;
                if (($action === 'recreate' && $tail === []) || count($tail) !== count(array_unique($tail)) || array_diff($tail, $allowed) !== []) {
                    throw new InvalidArgumentException('Provide explicit unique known services; build requires local build definitions.');
                }
            }
            if ($action === 'embeddings' && (count($tail) !== 1 || ! in_array($tail[0], ['auto', 'cpu', 'cuda'], true))) {
                throw new InvalidArgumentException('Embedding device must be auto, cpu or cuda.');
            }
            if ($action === 'profile' && $tail !== []) {
                if (! in_array($tail[0], ['low', 'standard', 'high', 'custom'], true) || ($tail[0] !== 'custom' && count($tail) !== 1)) {
                    throw new InvalidArgumentException('Select a known profile; overrides require custom.');
                }
                foreach (array_slice($tail, 1) as $setting) {
                    if (preg_match('/\A(?:DEV_CPU_LIMIT|DEV_MEMORY_LIMIT|TEST_WORKERS|PLAYWRIGHT_WORKERS)=[^\s=]+\z/D', $setting) !== 1) {
                        throw new InvalidArgumentException('Invalid custom resource setting.');
                    }
                }
            }
        }
        if ($this->tool === 'production') {
            $tail = array_slice($arguments, 1);
            if ($tail !== [] && (! in_array($arguments[0], ['restart', 'logs'], true) || count($tail) !== 1 || ! in_array($tail[0], ['app', 'nginx', 'postgres', 'queue', 'scheduler'], true))) {
                throw new InvalidArgumentException('Only production restart/logs accept one known service.');
            }
        }
        if ($this->tool === 'backup' && $arguments[0] !== 'create') {
            if (! isset($arguments[1]) || preg_match('/\Abackup-[0-9]{8}T[0-9]{12}Z\z/D', $arguments[1]) !== 1) {
                throw new InvalidArgumentException('Provide a backup ID, not a path.');
            }
            if ($arguments[0] === 'restore' && ! in_array('--confirm-runa-prod-restore', $arguments, true)) {
                throw new InvalidArgumentException('Restore requires explicit --confirm-runa-prod-restore.');
            }
        }
        if ($this->tool === 'project') {
            $evidence = $this->input->getParameterOption('--evidence', null);
            if (! is_string($evidence) || $evidence === '') {
                throw new InvalidArgumentException('Production state recording requires --evidence.');
            }
        }
        if ($this->tool === 'knowledge') {
            $device = $this->input->getParameterOption('--device', null);
            $topK = $this->input->getParameterOption('--top-k', null);
            if (($device !== null && ! in_array($device, ['auto', 'cpu'], true)) || ($topK !== null && (! is_string($topK) || preg_match('/\A[1-9][0-9]*\z/D', $topK) !== 1))) {
                throw new InvalidArgumentException('Invalid knowledge device or top-k.');
            }
        }
        if ($this->tool === 'knowledge' && $arguments[0] === 'search' && ! isset($arguments[1])) {
            throw new InvalidArgumentException('Knowledge search requires a query.');
        }
    }
}
