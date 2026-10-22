<?php

namespace Runa\Support\Modules\Development;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Runa\Contracts\Modules\ApiProvider;
use Runa\Contracts\Modules\IntegrationProvider;
use Runa\Contracts\Modules\JobProvider;
use Runa\Contracts\Modules\ManagementProvider;
use Runa\Contracts\Modules\ScheduleProvider;
use Runa\Contracts\Modules\SlotProvider;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\ModuleDeclarations;
use Runa\Support\Modules\Api\ModuleHttp;
use Runa\Support\Modules\Console\ModuleConsole;
use Runa\Support\Modules\Data\ModuleDataPaths;
use Runa\Support\Modules\Events\EventDefinitions;
use Runa\Support\Modules\Frontend\ModuleFrontend;
use Runa\Support\Modules\HealthPolicies;
use Runa\Support\Modules\LifecyclePolicies;
use Runa\Support\Modules\ModuleDiscovery;
use Runa\Support\Modules\ModuleGraph;
use Runa\Support\Modules\ModuleRecord;
use Runa\Support\Modules\ModuleSource;
use Runa\Support\Modules\RegistrySnapshot;
use Runa\Support\Modules\Settings\SettingsDefinitions;
use Runa\Support\Modules\Updates\UpgradeDefinitions;
use Runa\Support\Modules\Work\WorkDefinitions;
use Throwable;

/** Reusable checks, not a PHP/SQL sandbox. Runtime mode loads trusted pure descriptors only. */
final readonly class ModuleContractSuite
{
    public function __construct(private string $root, private bool $development = false) {}

    /** @return list<string> Safe relative-path diagnostics. Discovery errors deliberately propagate. */
    public function source(string $id): array
    {
        $sources = [...(new ModuleDiscovery($this->root, $this->development))->discover('runa'), ...(new ModuleDiscovery($this->root, $this->development))->discover('app')];
        $found = false;
        $errors = $records = [];
        foreach ($sources as $source) {
            $manifest = $source->manifest;
            /** @var array{required: array<string,string>, optional: array<string,string>} $dependencies */
            $dependencies = json_decode(json_encode($manifest->definition['dependencies'], JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
            if (! is_string($manifest->definition['platform']) || ! is_string($manifest->definition['framework_api'])) {
                throw new \RuntimeException('Invalid version constraints.');
            }
            $records[$manifest->id] = new ModuleRecord('', $manifest->id, $manifest->kind, true, true, false, $manifest->version, null, $source->path, $source->manifestHash, $dependencies['required'], $dependencies['optional'], $manifest->definition['platform'], $manifest->definition['framework_api']);
            if ($manifest->id !== $id) {
                continue;
            }
            $found = true;
            $directory = $this->root.'/'.$source->path;
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)) as $file) {
                if (! $file instanceof \SplFileInfo || ! $file->isFile()) {
                    continue;
                }
                $relative = substr($file->getPathname(), strlen($directory) + 1);
                if (str_starts_with($relative, 'tests/') || ! in_array($file->getExtension(), ['php', 'ts', 'vue', 'js'], true)) {
                    continue;
                }
                $text = (string) file_get_contents($file->getPathname());
                foreach ($this->lint($text, $manifest->prefix, $manifest->owner.'_'.$manifest->code, $file->getExtension()) as $code) {
                    $errors[] = $relative.':'.$code;
                }
            }
        }
        if (! $found) {
            throw new \InvalidArgumentException('Module source not found.');
        }
        $platform = json_decode((string) file_get_contents($this->root.'/runa/platform.json'), true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($platform) || ! is_string($platform['version'] ?? null)) {
            throw new \RuntimeException('Platform version unavailable.');
        }
        foreach ((new ModuleGraph(new RegistrySnapshot(0, $records), $platform['version']))->preview('enable', $id)->blockers as $blocker) {
            $errors[] = 'graph:'.$blocker->code.':'.implode('>', $blocker->path);
        }

        return array_values(array_unique($errors));
    }

    /** Pragmatic literal checks. Dynamic names/aliases/SQL assembly require human review.
     * @return list<string>
     */
    public function lint(string $text, string $prefix, string $schema, string $extension = 'php'): array
    {
        $errors = [];
        // Tokenize PHP to ignore comments while retaining names, strings and executable calls.
        if ($extension === 'php') {
            $text = implode('', array_map(static fn ($token) => is_array($token) ? (in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true) ? '' : $token[1]) : $token, token_get_all($text)));
            preg_match_all('/(?:App|Runa)\\\\Modules\\\\[A-Za-z][A-Za-z0-9]*(?:\\\\[A-Za-z][A-Za-z0-9]*)*/', $text, $matches);
            foreach ($matches[0] as $name) {
                if (! str_starts_with($name.'\\', $prefix) && ! str_contains($name, '\\Contracts\\')) {
                    $errors[] = 'foreign_module_import';
                }
            }
            if (preg_match('/\b(?:Route|Schedule|Queue|Event)\s*::|\bimplements\s+[^;{]*\bShouldQueue\b|\bextends\s+(?:\\\\?Illuminate\\\\Console\\\\)?Command\b/', $text) === 1) {
                $errors[] = 'unclassified_entrypoint';
            }
        }
        preg_match_all('/\b((?:app|runa)_[a-z][a-z0-9_]*)\.[a-z][a-z0-9_]*/', $text, $schemas);
        foreach ($schemas[1] as $foreign) {
            if ($foreign !== $schema) {
                $errors[] = 'foreign_schema_reference';
            }
        }
        if (preg_match('~(?:app|runa)/modules/|modules/(?:app|runa)/~', $text) === 1) {
            $errors[] = 'direct_module_path';
        }
        if ($extension !== 'php' && preg_match('~(?:\.\./)+(?:[a-z0-9_]+/)*modules/|@(?:app|runa)/modules/~', $text) === 1) {
            $errors[] = 'foreign_frontend_import';
        }

        return array_values(array_unique($errors));
    }

    /** Uses normal resolvers and their synchronization/ownership/type checks, without hooks/jobs/checks.
     * @return list<string>
     */
    public function runtime(ModuleRecord $record): array
    {
        try {
            $declarations = app(ModuleDeclarations::class);
            $manifest = $declarations->manifest($record);
            /** @var array<string, list<string>> $categories */
            $categories = (array) $manifest->definition['declarations'];
            $policy = app(LifecyclePolicies::class)->resolve($record);
            $policy->plan('enable');
            $policy->plan('disable');
            app(HealthPolicies::class)->resolve($record)?->definition($record->healthOverrides);
            foreach ($categories as $category => $classes) {
                $interface = match ($category) {
                    'apis' => 'ApiProvider', 'health' => 'HealthPolicy', 'lifecycle' => 'LifecyclePolicy',
                    'http' => 'HttpProvider', 'commands' => 'CommandProvider', 'jobs' => 'JobProvider',
                    'schedules' => 'ScheduleProvider', 'events' => 'EventProvider', 'frontend' => 'FrontendProvider',
                    'slots' => 'SlotProvider', 'management' => 'ManagementProvider', 'settings' => 'SettingsProvider',
                    'integrations' => 'IntegrationProvider', 'upgrades' => 'UpgradeProvider',
                };
                foreach ($classes as $class) {
                    $instance = $declarations->make($record, $class, 'Runa\\Contracts\\Modules\\'.$interface);
                    if (method_exists($instance, 'schemaVersion') && $instance->schemaVersion() !== 1) {
                        throw new \RuntimeException('Unsupported declaration schema.');
                    }
                    if ($instance instanceof ScheduleProvider) {
                        $instance->schedules();
                    }
                    if ($instance instanceof SlotProvider) {
                        $instance->hosts();
                        $instance->contributions();
                    }
                    if ($instance instanceof ManagementProvider) {
                        $instance->permissions();
                        $instance->actions();
                        $instance->components();
                    }
                    if ($instance instanceof IntegrationProvider) {
                        $instance->integrations();
                    }
                    if ($instance instanceof ApiProvider) {
                        $definition = $instance->definition();
                        app(ModuleApis::class)->resolve($record->id, $definition->contract, $definition->version);
                    }
                    if ($instance instanceof JobProvider) {
                        foreach ($instance->jobs() as $job) {
                            app(WorkDefinitions::class)->resolve($record->id, $job->key);
                        }
                    }
                }
            }
            if ($categories['settings'] !== []) {
                app(SettingsDefinitions::class)->for($record);
            }
            app(ModuleHttp::class)->endpoints($record);
            app(EventDefinitions::class)->for($record);
            app(ModuleConsole::class)->commands($record);
            app(ModuleFrontend::class)->pages($record);
            $paths = new ModuleDataPaths($this->root, $this->development);
            $paths->migrations($record);
            $paths->seeders($record);
            (new UpgradeDefinitions($this->root))->steps(new ModuleSource($manifest, $record->sourcePath, $record->manifestHash), $record->installedVersion);

            return [];
        } catch (Throwable) {
            return ['runtime:invalid_or_stale_descriptor'];
        }
    }
}
