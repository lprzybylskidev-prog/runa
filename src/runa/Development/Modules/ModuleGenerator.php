<?php

namespace Runa\Development\Modules;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Runa\ModuleFramework\ModuleDiscovery;
use Runa\ModuleFramework\ModuleManifest;
use Runa\Support\Modules\ModuleIdentity;
use RuntimeException;
use Throwable;

/** Development-only source generation. No registry, migration or activation writes. */
final readonly class ModuleGenerator
{
    public function __construct(private string $root, private bool $development) {}

    /** @return array<string, string> Repository-relative paths and complete candidate contents. */
    public function plan(string $id, string $type = 'module', ?string $name = null): array
    {
        $module = new ModuleIdentity($id);
        $this->context($module->owner);
        if (in_array($module->code, ['platform', 'application', 'shared', 'public', 'system', 'reference', 'module_probe'], true)) {
            throw new InvalidArgumentException('Reserved module identities cannot be generated.');
        }
        $namespace = ($module->owner === 'app' ? 'App' : 'Runa').'\\Modules\\'.Str::studly($module->code);
        $directory = 'src/'.$module->owner.'/modules/'.$module->code;
        $docs = ($module->owner === 'app' ? 'docs/app/modules/' : 'docs/runa/handbook/modules/').$module->code;
        $tests = 'tests/backend/'.$module->owner.'/modules/'.$module->code;
        $tokens = ['id' => $id, 'owner' => $module->owner, 'code' => $module->code, 'namespace' => $namespace, 'schema' => $module->schema(), 'testcase' => $module->owner === 'app' ? 'Tests\\TestCase' : 'Tests\\PlatformTestCase', 'test_command' => $module->owner === 'app' ? './bin/artisan runa:test --scope=backend ../tests/backend/app/modules/'.$module->code : 'python3 tools/maintainer/test-backend ../tests/backend/runa/modules/'.$module->code];
        $files = [];
        if ($type === 'module') {
            if ($name !== null || file_exists($this->root.'/'.$directory) || is_link($this->root.'/'.$directory)
                || file_exists($this->root.'/'.$docs) || is_link($this->root.'/'.$docs)
                || file_exists($this->root.'/'.$tests) || is_link($this->root.'/'.$tests)) {
                throw new InvalidArgumentException('Skeleton targets must be new source and documentation directories.');
            }
            $declarations = array_fill_keys(ModuleManifest::CATEGORIES, []);
            $declarations['lifecycle'] = [$namespace.'\\Definitions\\Lifecycle'];
            $platform = json_decode((string) file_get_contents($this->root.'/src/runa/platform.json'), true, flags: JSON_THROW_ON_ERROR);
            if (! is_array($platform) || ! is_string($platform['version'] ?? null)) {
                throw new RuntimeException('Platform version unavailable.');
            }
            $manifest = [
                'schema_version' => 1, 'id' => $id, 'kind' => 'optional', 'version' => '0.1.0',
                'platform' => '^'.$platform['version'], 'framework_api' => '^1.0.0',
                'name' => ['pl' => $module->code, 'en' => $module->code],
                'description' => ['pl' => 'Moduł '.$module->code, 'en' => 'Module '.$module->code],
                'dependencies' => ['required' => (object) [], 'optional' => (object) []],
                'providers' => [], 'declarations' => $declarations, 'docs' => [$docs.'/README.md'], 'tests' => [$tests],
            ];
            $files[$directory.'/module.json'] = $this->json($manifest);
            $files[$directory.'/backend/Definitions/Lifecycle.php'] = $this->stub('lifecycle.php', $tokens);
            $files[$tests.'/ModuleContractTest.php'] = $this->stub('test.php', $tokens);
            $files[$docs.'/README.md'] = $this->stub('README.md', $tokens);
            $files[$docs.'/CHANGELOG.md'] = $this->stub('CHANGELOG.md', $tokens);
        } else {
            if (! in_array($type, ['controller', 'model', 'migration', 'service', 'action', 'api', 'component', 'test'], true)
                || $name === null || preg_match('/\A[A-Z][A-Za-z0-9]{0,79}\z/D', $name) !== 1) {
                throw new InvalidArgumentException('Select a supported element and a simple PascalCase name.');
            }
            try {
                $nameTokens = token_get_all('<?php class '.$name.' {}', TOKEN_PARSE);
                $identifiers = array_filter($nameTokens, static fn ($token) => is_array($token) && $token[0] === T_STRING && $token[1] === $name);
                if ($identifiers === [] || in_array(strtolower($name), ['enum', 'self', 'parent', 'static', 'string', 'int', 'float', 'bool', 'array', 'object', 'mixed', 'void', 'never', 'callable', 'iterable', 'resource', 'true', 'false', 'null'], true)) {
                    throw new InvalidArgumentException('Element name collides with a PHP type or reserved identifier.');
                }
            } catch (\ParseError) {
                throw new InvalidArgumentException('Element name must be a valid PHP class identifier.');
            }
            $source = null;
            foreach ((new ModuleDiscovery($this->root.'/src', true))->discover($module->owner) as $candidate) {
                if ($candidate->manifest->id === $id) {
                    $source = $candidate;
                }
            }
            if ($source === null) {
                throw new InvalidArgumentException('Generate elements only inside a valid existing module.');
            }
            $tokens += ['name' => $name, 'table' => Str::snake(Str::pluralStudly($name)), 'slug' => Str::kebab($name), 'operation' => Str::snake($name).'.read'];
            $module->table($tokens['table']);
            $templates = match ($type) {
                'controller' => ['backend/Http/Controllers/'.$name.'.php' => 'controller.php', 'backend/Definitions/'.$name.'Http.php' => 'http.php'],
                'model' => ['backend/Models/'.$name.'.php' => 'model.php'],
                'migration' => ['database/migrations/'.gmdate('Y_m_d_His').'_'.$module->schema().'_create_'.$tokens['table'].'.php' => 'migration.php'],
                'api' => ['backend/Contracts/'.$name.'.php' => 'contract.php', 'backend/Handlers/'.$name.'Handler.php' => 'handler.php', 'backend/Proxies/'.$name.'Proxy.php' => 'proxy.php', 'backend/Definitions/'.$name.'Api.php' => 'api.php'],
                'component' => ['frontend/components/'.Str::snake($name).'.vue' => 'component.vue'],
                'test' => [$name.'.php' => 'test.php'],
                default => ['backend/'.($type === 'action' ? 'Actions' : 'Services').'/'.$name.'.php' => $type.'.php'],
            };
            foreach ($templates as $path => $stub) {
                $files[($type === 'test' ? $tests : $directory).'/'.$path] = $this->stub($stub, $tokens);
            }
            if (in_array($type, ['controller', 'api'], true)) {
                $manifest = json_decode((string) file_get_contents($this->root.'/'.$directory.'/module.json'), true, flags: JSON_THROW_ON_ERROR);
                if (! is_array($manifest) || ! is_array($manifest['declarations'] ?? null)) {
                    throw new RuntimeException('Invalid module manifest.');
                }
                $category = $type === 'api' ? 'apis' : 'http';
                $class = $namespace.'\\Definitions\\'.$name.($type === 'api' ? 'Api' : 'Http');
                $entries = $manifest['declarations'][$category];
                if (! is_array($entries)) {
                    throw new RuntimeException('Invalid module declarations.');
                }
                if (! in_array($class, $entries, true)) {
                    $entries[] = $class;
                    $manifest['declarations'][$category] = $entries;
                    // Preserve empty JSON objects in dependency maps.
                    $manifest['dependencies'] = $source->manifest->definition['dependencies'];
                    $files[$directory.'/module.json'] = $this->json($manifest);
                }
            }
        }
        foreach ($files as $path => $_) {
            $this->safe($path);
        }

        return $files;
    }

    /** Exact hashes are explicit scoped overwrite authorization, including manifest edits.
     * @param  array<string, string>  $files
     * @param  array<string, string>  $overwrites
     */
    public function write(array $files, array $overwrites = []): void
    {
        if (array_diff_key($overwrites, $files) !== []) {
            throw new InvalidArgumentException('Overwrite authorization is outside the generation plan.');
        }
        foreach (array_keys($files) as $path) {
            $this->safe($path);
            $this->context(str_starts_with($path, 'src/runa/') || str_starts_with($path, 'docs/runa/') || str_starts_with($path, 'tests/backend/runa/') ? 'runa' : 'app');
        }
        $lock = fopen(sys_get_temp_dir().'/runa-generator-'.hash('sha256', $this->root).'.lock', 'c');
        if ($lock === false || ! flock($lock, LOCK_EX | LOCK_NB)) {
            throw new RuntimeException('Another source generator is running.');
        }
        $previous = $createdDirectories = $written = [];
        try {
            foreach ($files as $path => $content) {
                $this->safe($path);
                $target = $this->root.'/'.$path;
                $previous[$path] = is_file($target) ? file_get_contents($target) : null;
                if ($previous[$path] === false || (file_exists($target) && ! is_file($target))) {
                    throw new RuntimeException('Generation target is not a regular file.');
                }
                if ($previous[$path] !== null && (! isset($overwrites[$path]) || ! hash_equals(hash('sha256', $previous[$path]), $overwrites[$path]))) {
                    throw new RuntimeException('Existing target requires its exact current SHA-256 overwrite authorization.');
                }
            }
            foreach ($files as $path => $content) {
                $target = $this->root.'/'.$path;
                $missing = [];
                for ($parent = dirname($target); ! is_dir($parent); $parent = dirname($parent)) {
                    $missing[] = $parent;
                }
                foreach (array_reverse($missing) as $parent) {
                    if (! mkdir($parent, 0755)) {
                        throw new RuntimeException('Cannot create generation directory.');
                    }
                    $createdDirectories[] = $parent;
                }
                $this->safe($path);
                $stream = fopen($target, $previous[$path] === null ? 'x' : 'c+');
                if ($stream === false) {
                    throw new RuntimeException('Cannot open generation target.');
                }
                $written[] = $path;
                try {
                    if (! ftruncate($stream, 0) || fwrite($stream, $content) !== strlen($content)) {
                        throw new RuntimeException('Cannot write complete generated source.');
                    }
                } finally {
                    fclose($stream);
                }
            }
            // Validate the complete candidate layout; restore all files if discovery rejects it.
            foreach (['app', 'runa'] as $owner) {
                (new ModuleDiscovery($this->root.'/src', true))->discover($owner);
            }
        } catch (Throwable $error) {
            foreach (array_reverse($written) as $path) {
                if ($previous[$path] === null) {
                    unlink($this->root.'/'.$path);
                } else {
                    file_put_contents($this->root.'/'.$path, $previous[$path]);
                }
            }
            foreach (array_reverse($createdDirectories) as $directory) {
                rmdir($directory);
            }
            throw $error;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function context(string $owner): void
    {
        if (! $this->development || realpath($this->root) !== $this->root || is_link($this->root.'/.git')) {
            throw new InvalidArgumentException('Module generation requires canonical development source.');
        }
        $receipt = $this->root.'/.git/runa-bootstrap.json';
        if (is_link($receipt)) {
            throw new InvalidArgumentException('Development receipt cannot traverse symlinks.');
        }
        if (is_file($receipt)) {
            $installation = json_decode((string) file_get_contents($receipt), true, flags: JSON_THROW_ON_ERROR);
            if (! is_array($installation) || ($installation['schema_version'] ?? null) !== 1 || ($installation['mode'] ?? null) !== 'dev') {
                throw new InvalidArgumentException('Module generation requires a known development installation.');
            }
        }
        if ($owner === 'runa' && (! is_dir($this->root.'/.git') || file_exists($this->root.'/.git/runa-bootstrap.json') || is_link($this->root.'/.git/runa-bootstrap.json')
            || ! is_dir($this->root.'/tools/maintainer') || ! is_file($this->root.'/docs/runa/maintainer/agent-contract.md'))) {
            throw new InvalidArgumentException('Runa module generation requires a receipt-free platform owner checkout.');
        }
    }

    private function safe(string $relative): void
    {
        if (preg_match('~\A(?:src/(?:app|runa)/modules/[a-z][a-z0-9_]*|docs/(?:app|runa/handbook)/modules/[a-z][a-z0-9_]*|tests/backend/(?:app|runa)/modules/[a-z][a-z0-9_]*)/(?:[A-Za-z0-9_-]+/)*[A-Za-z0-9_.-]+\z~D', $relative) !== 1
            || in_array('..', explode('/', $relative), true)) {
            throw new InvalidArgumentException('Generated path is outside owned module roots.');
        }
        foreach (explode('/', $relative) as $part) {
            $current = ($current ?? $this->root).'/'.$part;
            if (is_link($current) || (file_exists($current) && realpath($current) !== $current)) {
                throw new InvalidArgumentException('Generated paths must not traverse symlinks.');
            }
        }
    }

    /** @param array<string, string> $tokens */
    private function stub(string $name, array $tokens): string
    {
        $template = file_get_contents(__DIR__.'/stubs/'.$name.'.stub');
        if ($template === false) {
            throw new RuntimeException('Module template unavailable.');
        }
        foreach ($tokens as $key => $value) {
            $template = str_replace('{{'.$key.'}}', $value, $template);
        }

        return $template;
    }

    /** @param array<mixed> $definition */
    private function json(array $definition): string
    {
        return json_encode($definition, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n";
    }
}
