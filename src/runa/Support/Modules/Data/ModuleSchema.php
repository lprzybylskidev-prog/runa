<?php

namespace Runa\Support\Modules\Data;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Runa\Support\Modules\ModuleIdentity;
use RuntimeException;

/** Explicit migration primitive, never called by request boot or lifecycle toggles. */
final readonly class ModuleSchema
{
    public function __construct(private Connection $connection, public ModuleIdentity $module) {}

    /** @param Closure(Blueprint): mixed $columns */
    public function create(string $table, Closure $columns, bool $exposed = true): void
    {
        $qualified = $this->module->table($table);
        $this->connection->transaction(function () use ($qualified, $columns, $exposed): void {
            $this->claim();
            $this->connection->getSchemaBuilder()->create($qualified, function (Blueprint $blueprint) use ($qualified, $columns, $exposed): void {
                $blueprint->bigIncrements('id')->generatedAs()->always();
                if ($exposed) {
                    // Laravel's schema/table-derived default can exceed PostgreSQL's identifier limit.
                    $blueprint->uuid('public_id')->unique('module_'.substr(hash('sha256', $qualified), 0, 24).'_public_id_unique');
                }
                $columns($blueprint);
            });
            if ($exposed) {
                $this->connection->statement('ALTER TABLE '.$qualified." ADD CHECK (public_id::text ~ '^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$')");
            }
            $this->assertForeignKeys();
        });
    }

    /** @param Closure(Blueprint): mixed $columns */
    public function table(string $table, Closure $columns): void
    {
        $qualified = $this->module->table($table);
        $this->connection->transaction(function () use ($qualified, $columns): void {
            $this->claim();
            $this->connection->getSchemaBuilder()->table($qualified, $columns);
            $this->assertForeignKeys();
        });
    }

    public function claim(): void
    {
        if ($this->connection->getDriverName() !== 'pgsql' || $this->connection->transactionLevel() === 0) {
            throw new RuntimeException('Module schema ownership requires a PostgreSQL transaction.');
        }
        $schema = $this->module->schema();
        // Serialize creation/adoption checks across independent installation workers.
        $this->connection->select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', ['module-schema:'.$schema]);
        $record = $this->connection->table('shared.modules')->where('module_id', $this->module->id)->first();
        if ($record === null || ! $record->registered || ! $record->source_present) {
            throw new RuntimeException('Register trusted present module source before migrating its schema.');
        }
        $existing = $this->connection->selectOne('SELECT obj_description(oid, \'pg_namespace\') AS owner FROM pg_namespace WHERE nspname = ?', [$schema]);
        $marker = 'runa.module.v1:'.$this->module->id;
        if ($existing !== null) {
            if (! is_object($existing) || ! property_exists($existing, 'owner') || $existing->owner !== $marker) {
                throw new RuntimeException('Module schema collides with an unowned or differently owned schema.');
            }

            return;
        }
        $this->connection->statement('CREATE SCHEMA '.$schema);
        // Identity-derived identifiers and marker contain only validated ASCII characters.
        $this->connection->statement('COMMENT ON SCHEMA '.$schema." IS '".$marker."'");
    }

    public function assertForeignKeys(): void
    {
        $invalid = $this->connection->selectOne(<<<'SQL'
            SELECT c.conname FROM pg_constraint c
            JOIN pg_class source ON source.oid = c.conrelid
            JOIN pg_namespace sn ON sn.oid = source.relnamespace
            JOIN pg_class target ON target.oid = c.confrelid
            JOIN pg_namespace tn ON tn.oid = target.relnamespace
            WHERE c.contype = 'f' AND (sn.nspname = ? OR tn.nspname = ?)
            AND (sn.nspname <> tn.nspname OR c.confdeltype <> 'r' OR c.confupdtype <> 'r') LIMIT 1
            SQL, [$this->module->schema(), $this->module->schema()]);
        if ($invalid !== null) {
            throw new RuntimeException('Module foreign keys must remain in the owning schema with RESTRICT actions.');
        }
    }
}
