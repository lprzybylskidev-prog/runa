<?php

namespace Runa\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Seeder;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;

final readonly class DatabaseScope
{
    // Protected source inventory; update alongside each new platform-owned table.
    public const PLATFORM_TABLES = ['shared.migrations', 'shared.jobs', 'shared.failed_jobs', 'shared.platform_seed_runs'];

    public function __construct(public string $root, public bool $platform = false) {}

    public function repository(): string
    {
        return $this->platform ? 'shared.migrations' : 'shared.application_migrations';
    }

    public function migrationPath(): string
    {
        return $this->root.($this->platform ? '/runa' : '/app').'/database/migrations';
    }

    /** @return list<string> */
    public function paths(InputInterface $input): array
    {
        $paths = $input->hasOption('path') ? $input->getOption('path') : [];
        $paths = $paths ?: [$this->migrationPath()];
        $root = realpath($this->migrationPath());
        if ($root !== $this->migrationPath()) {
            throw new RuntimeException('Migration owner root must not traverse symlinks.');
        }
        $result = [];
        foreach ((array) $paths as $path) {
            if (! is_string($path)) {
                throw new RuntimeException('Migration paths must be strings.');
            }
            if (! str_starts_with($path, '/')) {
                $path = $this->root.'/'.$path;
            }
            $resolved = realpath($path);
            if ($resolved === false || ($resolved !== $root && ! str_starts_with($resolved, $root.'/'))) {
                throw new RuntimeException('Migration path escapes the selected database owner.');
            }
            foreach (is_dir($resolved) ? (glob($resolved.'/*.php') ?: []) : [] as $file) {
                $target = realpath($file);
                if ($target === false || ! str_starts_with($target, $root.'/')) {
                    throw new RuntimeException('Migration file escapes the selected database owner.');
                }
            }
            $result[] = $resolved;
        }

        return $result;
    }

    public function validate(InputInterface $input): void
    {
        $database = $input->hasOption('database') ? $input->getOption('database') : null;
        if ($database !== null && $database !== 'pgsql') {
            throw new RuntimeException('Database operations only support the guarded pgsql connection.');
        }
        foreach (['drop-views', 'drop-types', 'schema-path', 'graceful'] as $option) {
            if ($input->hasOption($option) && $input->getOption($option)) {
                throw new RuntimeException('Global schema dumps, views/types wipes and suppressed migration failures are not supported by scoped database operations.');
            }
        }
        if ($input->hasOption('schema-path') && (glob(database_path('schema/*.sql')) ?: []) !== []) {
            throw new RuntimeException('Whole-database schema restores are not supported by scoped migrations.');
        }
        if ($input->hasOption('path')) {
            $this->paths($input);
        }
        if ($input->hasOption('seeder') && $input->getOption('seeder')) {
            $seeder = $input->getOption('seeder');
            if (! is_string($seeder)) {
                throw new RuntimeException('Seeder must be a class name.');
            }
            $this->seeder($seeder);
        }
        if (config('database.default') !== 'pgsql') {
            throw new RuntimeException('Scoped migrations require the pgsql connection.');
        }
    }

    /** @return class-string<Seeder> */
    public function seeder(string $class): string
    {
        if (! str_contains($class, '\\')) {
            $class = ($this->platform ? 'Runa\\Database\\Seeders\\' : 'Database\\Seeders\\').$class;
        }
        if (! $this->platform && str_starts_with($class, 'Runa\\')) {
            throw new RuntimeException('Seeder escapes the selected database owner.');
        }
        $directory = realpath($this->root.($this->platform ? '/runa/database/seeders' : '/app'));
        if ($directory !== $this->root.($this->platform ? '/runa/database/seeders' : '/app')) {
            throw new RuntimeException('Seeder owner root must not traverse symlinks.');
        }
        $file = class_exists($class) ? (new ReflectionClass($class))->getFileName() : false;
        if ($file === false || ! str_starts_with((string) realpath($file), $directory.'/') || ! is_subclass_of($class, Seeder::class)) {
            throw new RuntimeException('Seeder escapes the selected database owner.');
        }

        return $class;
    }

    public function assertHistory(Connection $connection): void
    {
        if (! $connection->getSchemaBuilder()->hasTable('shared.migrations')) {
            return;
        }
        $known = array_map(static fn (string $file): string => basename($file, '.php'), glob($this->root.'/runa/database/migrations/*.php') ?: []);
        /** @var list<string> $applied */
        $applied = $connection->table('shared.migrations')->pluck('migration')->all();
        if (array_diff($applied, $known) !== []) {
            throw new RuntimeException('Legacy mixed or unknown platform migration history requires an explicit owner transition; no history or data was changed.');
        }
    }

    public function wipe(Connection $connection): void
    {
        if ($connection->getDriverName() !== 'pgsql') {
            throw new RuntimeException('Scoped wipes require PostgreSQL.');
        }
        $connection->transaction(function () use ($connection): void {
            // Extension-owned objects and system schemas are never application-owned.
            /** @var list<object{schema: string, name: string}> $tables */
            $tables = $connection->select("SELECT n.nspname AS schema, c.relname AS name FROM pg_class c JOIN pg_namespace n ON n.oid = c.relnamespace WHERE c.relkind IN ('r', 'p') AND n.nspname NOT LIKE 'pg_%' AND n.nspname <> 'information_schema' AND NOT EXISTS (SELECT 1 FROM pg_depend d WHERE d.objid = c.oid AND d.classid = 'pg_class'::regclass AND d.deptype = 'e') ORDER BY 1,2");
            $owned = [];
            foreach ($tables as $table) {
                $name = $table->schema.'.'.$table->name;
                if (in_array($name, self::PLATFORM_TABLES, true) === $this->platform) {
                    if ($table->schema === 'public') {
                        throw new RuntimeException('Unowned tables in public block scoped wipe; inspect database ownership first.');
                    }
                    $owned[] = '"'.str_replace('"', '""', $table->schema).'"."'.str_replace('"', '""', $table->name).'"';
                }
            }
            if ($owned !== []) {
                // One statement handles same-owner foreign keys; RESTRICT preserves all cross-owner dependants.
                $connection->statement('DROP TABLE '.implode(', ', $owned).' RESTRICT');
            }
        });
    }
}
