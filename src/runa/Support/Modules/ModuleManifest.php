<?php

namespace Runa\Support\Modules;

use InvalidArgumentException;
use Runa\Support\VersionCompatibility;
use stdClass;

final readonly class ModuleManifest
{
    public const CATEGORIES = ['apis', 'health', 'lifecycle', 'settings', 'integrations', 'http', 'commands', 'jobs', 'schedules', 'events', 'frontend', 'slots', 'management', 'upgrades'];

    /** @param array<string, mixed> $definition
     * @param  list<string>  $classes
     * @param  list<string>  $paths
     */
    private function __construct(public string $id, public string $owner, public string $code, public string $kind, public string $version, public string $prefix, public array $definition, public array $classes, public array $paths) {}

    public static function load(string $file, string $owner, string $code, bool $probe = false): self
    {
        if (! is_file($file) || is_link($file) || filesize($file) > 65536) {
            throw new InvalidArgumentException('Module manifest must be a regular JSON file up to 64 KiB.');
        }
        $json = file_get_contents($file);
        if ($json === false) {
            throw new InvalidArgumentException('Module manifest cannot be read.');
        }
        $object = json_decode($json, false, 16, JSON_THROW_ON_ERROR);
        self::uniqueKeys($json);
        $data = self::object($object, ['schema_version', 'id', 'kind', 'version', 'platform', 'framework_api', 'name', 'description', 'dependencies', 'providers', 'declarations', 'docs', 'tests']);
        if (! in_array($owner, ['runa', 'app'], true) || ! self::validCode($code)
            || (in_array($code, ['platform', 'application', 'shared', 'public', 'system', 'reference', 'module_probe'], true) && ! ($probe && $owner === 'app' && in_array($code, ['module_probe', 'reference'], true)))
            || $data['schema_version'] !== 1 || $data['id'] !== $owner.'.'.$code || ! in_array($data['kind'], ['core', 'optional'], true)) {
            throw new InvalidArgumentException('Invalid module identity, kind or schema version.');
        }
        self::version($data['version']);
        foreach (['platform', 'framework_api'] as $key) {
            self::constraint($data[$key]);
        }
        foreach (['name' => 160, 'description' => 2000] as $key => $limit) {
            foreach (self::object($data[$key], ['pl', 'en']) as $value) {
                if (! is_string($value) || trim($value) === '' || mb_strlen($value) > $limit) {
                    throw new InvalidArgumentException('Invalid localized module metadata.');
                }
            }
        }
        $dependencies = self::object($data['dependencies'], ['required', 'optional']);
        $seen = [];
        foreach ($dependencies as $map) {
            if (! $map instanceof stdClass || count(get_object_vars($map)) > 256) {
                throw new InvalidArgumentException('Module dependencies must be bounded objects.');
            }
            foreach (get_object_vars($map) as $id => $constraint) {
                $parts = explode('.', $id);
                if (count($parts) !== 2 || ! in_array($parts[0], ['runa', 'app'], true) || ! self::validCode($parts[1])
                    || $id === $data['id'] || isset($seen[$id]) || ($owner === 'runa' && $parts[0] === 'app')) {
                    throw new InvalidArgumentException('Invalid module dependency identity or direction.');
                }
                self::constraint($constraint);
                $seen[$id] = true;
            }
        }
        $prefix = ($owner === 'app' ? 'App' : 'Runa').'\\Modules\\'.str_replace(' ', '', ucwords(str_replace('_', ' ', $code))).'\\';
        $ownedClasses = [];
        foreach (['providers' => $data['providers'], ...self::object($data['declarations'], self::CATEGORIES)] as $key => $classes) {
            self::stringList($classes);
            if ($key === 'lifecycle' && count($classes) !== 1) {
                throw new InvalidArgumentException('A module must declare exactly one lifecycle policy.');
            }
            if ($key === 'health' && count($classes) > 1) {
                throw new InvalidArgumentException('A module may declare at most one health policy.');
            }
            foreach ($classes as $class) {
                if (! str_starts_with($class, $prefix) || preg_match('/\A[A-Za-z_][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)+\z/D', $class) !== 1) {
                    throw new InvalidArgumentException('Module classes must use their owning namespace.');
                }
                $ownedClasses[] = $class;
            }
        }
        // Documentation is repository-root metadata, never a runtime source dependency.
        $docs = $data['docs'];
        self::stringList($docs);
        $documentationRoot = $owner === 'app' ? 'docs/app/modules/' : 'docs/runa/handbook/modules/';
        $documentationPrefix = $documentationRoot.$code.'/';
        foreach ($docs as $path) {
            if (! str_starts_with($path, $documentationPrefix) || ! str_ends_with($path, '.md')) {
                throw new InvalidArgumentException('Module documentation belongs in its central owner documentation directory.');
            }
            foreach (explode('/', substr($path, strlen($documentationPrefix))) as $segment) {
                if (preg_match('/\A[A-Za-z0-9][A-Za-z0-9_.-]*\z/D', $segment) !== 1) {
                    throw new InvalidArgumentException('Invalid module documentation path.');
                }
            }
        }
        $paths = $data['tests'];
        self::stringList($paths);
        foreach ($paths as $path) {
            if ($path !== 'tests' && ! str_starts_with($path, 'tests/')) {
                throw new InvalidArgumentException('Invalid module test path.');
            }
        }

        return new self($owner.'.'.$code, $owner, $code, $data['kind'], $data['version'], $prefix, $data, array_values(array_unique($ownedClasses)), $paths);
    }

    public static function validCode(string $code): bool
    {
        return strlen($code) <= 58 && preg_match('/\A[a-z][a-z0-9]*(?:_[a-z][a-z0-9]*)*\z/D', $code) === 1;
    }

    /** @param list<string> $keys
     * @return array<string, mixed>
     */
    private static function object(mixed $value, array $keys): array
    {
        if (! $value instanceof stdClass) {
            throw new InvalidArgumentException('Module manifest field must be an object.');
        }
        /** @var array<string, mixed> $data Object properties are named JSON fields. */
        $data = get_object_vars($value);
        if (array_diff(array_keys($data), $keys) !== [] || array_diff($keys, array_keys($data)) !== []) {
            throw new InvalidArgumentException('Unknown or missing module manifest fields.');
        }

        return $data;
    }

    /** @phpstan-assert list<string> $value */
    private static function stringList(mixed $value): void
    {
        if (! is_array($value) || ! array_is_list($value) || count($value) > 256) {
            throw new InvalidArgumentException('Module declarations must be bounded lists.');
        }
        foreach ($value as $entry) {
            if (! is_string($entry) || $entry === '' || strlen($entry) > 256) {
                throw new InvalidArgumentException('Invalid module list entry.');
            }
        }
        if (count(array_unique($value)) !== count($value)) {
            throw new InvalidArgumentException('Duplicate module list entry.');
        }
    }

    /** @phpstan-assert string $value */
    private static function version(mixed $value): void
    {
        if (! is_string($value) || preg_match('/\A(0|[1-9][0-9]{0,8})\.(0|[1-9][0-9]{0,8})\.(0|[1-9][0-9]{0,8})\z/D', $value) !== 1) {
            throw new InvalidArgumentException('Module versions require bounded stable X.Y.Z.');
        }
    }

    private static function constraint(mixed $value): void
    {
        if (! is_string($value)) {
            throw new InvalidArgumentException('Module version constraints must be strings.');
        }
        (new VersionCompatibility)->supports($value, '1.0.0');
    }

    private static function uniqueKeys(string $json): void
    {
        // JSON has already been parsed. Tokenize only strings and structure so escaped
        // equivalent keys are compared after decoding, without interpreting string contents.
        if (preg_match_all('/"(?:[^"\\\\]|\\\\.)*"|[{}\[\],:]/s', $json, $matches) === false) {
            throw new InvalidArgumentException('Module JSON exceeds tokenizer limits.');
        }
        $stack = [];
        foreach ($matches[0] as $token) {
            if ($token === '{' || $token === '[') {
                $stack[] = ['object' => $token === '{', 'key' => $token === '{', 'seen' => []];
            } elseif ($token === '}' || $token === ']') {
                array_pop($stack);
            } elseif ($stack !== []) {
                $index = array_key_last($stack);
                if ($token === ',' && $stack[$index]['object']) {
                    $stack[$index]['key'] = true;
                } elseif (str_starts_with($token, '"') && $stack[$index]['key']) {
                    /** @var string $key */
                    $key = json_decode($token, true, 16, JSON_THROW_ON_ERROR);
                    if (isset($stack[$index]['seen'][$key])) {
                        throw new InvalidArgumentException('Duplicate module JSON key.');
                    }
                    $stack[$index]['seen'][$key] = true;
                    $stack[$index]['key'] = false;
                }
            }
        }
    }
}
