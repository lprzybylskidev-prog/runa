<?php

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Runa\Contracts\Modules\PublicReferenceLookup;
use Runa\Database\DatabaseScope;
use Runa\ModuleFramework\Data\ModuleDataPaths;
use Runa\ModuleFramework\ModuleDiscovery;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\Support\Modules\Data\ModuleSchema;
use Runa\Support\Modules\Data\PublicReferenceRule;
use Runa\Support\Modules\ModuleIdentity;
use Tests\Fixtures\BoundaryRecord;
use Tests\Fixtures\ForeignBoundaryRecord;
use Tests\Fixtures\ModuleSources;

uses(DatabaseTransactions::class);

beforeEach(function (): void {
    $this->sources = new ModuleSources;
    $this->sources->add('app', 'boundary');
    $this->sources->add('runa', 'boundary');
    $this->registry = new ModuleRegistry(DB::connection(), new ModuleDiscovery($this->sources->root));
    $this->registry->synchronize('runa');
    $this->registry->synchronize('app');
    $this->schema = new ModuleSchema(DB::connection(), new ModuleIdentity('app.boundary'));
});

afterEach(function (): void {
    $this->sources->remove();
});

it('derives collision-free bounded identities and rejects SQL and path identifiers', function (): void {
    expect((new ModuleIdentity('app.'.str_repeat('a', 58)))->schema())->toHaveLength(62)
        ->and((new ModuleIdentity('runa.'.str_repeat('a', 58)))->schema())->toHaveLength(63)
        ->and((new ModuleIdentity('app.boundary'))->schema())->not->toBe((new ModuleIdentity('runa.boundary'))->schema());
    foreach (['app.'.str_repeat('a', 59), 'APP.foo', 'app.foo.bar', 'app.foo;drop', 'app../foo'] as $id) {
        expect(fn () => new ModuleIdentity($id))->toThrow(InvalidArgumentException::class);
    }
    foreach (['other.records', '../records', 'records;drop', str_repeat('a', 64)] as $name) {
        expect(fn () => $this->schema->module->table($name))->toThrow(InvalidArgumentException::class);
    }
});

it('creates actual bigint identity UUIDv7 business rows and same-module restrict foreign keys', function (): void {
    $this->schema->create('records', function (Blueprint $table): void {
        $table->text('name');
        $table->timestampsTz();
        $table->softDeletesTz();
    });
    $this->schema->create('children', function (Blueprint $table): void {
        $table->bigInteger('record_id');
        $table->foreign('record_id')->references('id')->on('app_boundary.records')->restrictOnDelete()->restrictOnUpdate();
    }, false);
    $ddl = DB::selectOne("SELECT data_type, is_identity, identity_generation FROM information_schema.columns WHERE table_schema = 'app_boundary' AND table_name = 'records' AND column_name = 'id'");
    expect($ddl->data_type)->toBe('bigint')->and($ddl->is_identity)->toBe('YES')->and($ddl->identity_generation)->toBe('ALWAYS');
    $record = BoundaryRecord::create(['name' => 'Owned']);
    expect($record->public_id[14])->toBe('7')->and($record->getRouteKeyName())->toBe('public_id')->and($record->toArray())->not->toHaveKey('id');
    DB::table('app_boundary.children')->insert(['record_id' => $record->id]);
    expect(fn () => DB::transaction(fn () => DB::table('app_boundary.records')->where('id', $record->id)->delete()))->toThrow(QueryException::class);
    foreach ([null, (string) Str::uuid(), $record->public_id] as $invalid) {
        expect(fn () => DB::transaction(fn () => DB::table('app_boundary.records')->insert(['public_id' => $invalid, 'name' => 'Invalid'])))->toThrow(QueryException::class);
    }
    expect(BoundaryRecord::query()->findOrFail($record->id)->resolveRouteBinding($record->public_id)->id)->toBe($record->id);
    expect(fn () => $record->resolveRouteBinding($record->id))->toThrow(RuntimeException::class);
    expect(fn () => $record->resolveRouteBinding($record->public_id, 'id'))->toThrow(RuntimeException::class);
    $record->delete();
    expect(BoundaryRecord::query()->count())->toBe(0)->and(BoundaryRecord::withTrashed()->count())->toBe(1);
});

it('refuses existing schema collisions without adopting or modifying their data', function (): void {
    DB::statement('CREATE SCHEMA app_boundary');
    DB::statement('CREATE TABLE app_boundary.ordinary (value text NOT NULL)');
    DB::table('app_boundary.ordinary')->insert(['value' => 'retained']);
    expect(fn () => $this->schema->create('records', fn (Blueprint $table) => $table->text('name')))->toThrow(RuntimeException::class, 'collides');
    expect(DB::table('app_boundary.ordinary')->value('value'))->toBe('retained')
        ->and(DB::getSchemaBuilder()->hasTable('app_boundary.records'))->toBeFalse();
});

it('creates maximum length schemas and tables without truncated unique-index collisions', function (): void {
    $code = str_repeat('a', 58);
    $this->sources->add('runa', $code);
    $this->registry->synchronize('runa');
    $schema = new ModuleSchema(DB::connection(), new ModuleIdentity('runa.'.$code));
    foreach ([str_repeat('b', 62).'a', str_repeat('b', 62).'b'] as $table) {
        $schema->create($table, fn (Blueprint $blueprint) => $blueprint->text('name'));
        expect(DB::getSchemaBuilder()->hasTable($schema->module->table($table)))->toBeTrue();
    }
    expect(DB::selectOne('SELECT count(*) AS count FROM pg_indexes WHERE schemaname = ?', [$schema->module->schema()])->count)->toBe(4);
});

it('rolls back cross-module FKs and unsafe referential actions including alterations', function (): void {
    $foreign = new ModuleSchema(DB::connection(), new ModuleIdentity('runa.boundary'));
    $foreign->create('records', fn (Blueprint $table) => $table->text('name'));
    foreach (['runa_boundary.records', 'shared.modules'] as $target) {
        expect(fn () => $this->schema->create('bad', function (Blueprint $table) use ($target): void {
            $table->bigInteger('foreign_id');
            $table->foreign('foreign_id')->references('id')->on($target)->restrictOnDelete()->restrictOnUpdate();
        }))->toThrow(RuntimeException::class, 'foreign keys');
        expect(DB::getSchemaBuilder()->hasTable('app_boundary.bad'))->toBeFalse();
    }
    $this->schema->create('records', fn (Blueprint $table) => $table->text('name'));
    foreach (['cascade', 'no action', 'set null'] as $action) {
        expect(fn () => $this->schema->create('bad', function (Blueprint $table) use ($action): void {
            $table->bigInteger('parent_id')->nullable();
            $table->foreign('parent_id')->references('id')->on('app_boundary.records')->onDelete($action)->restrictOnUpdate();
        }))->toThrow(RuntimeException::class);
    }
    expect(fn () => $this->schema->table('records', function (Blueprint $table): void {
        $table->bigInteger('foreign_id')->nullable();
        $table->foreign('foreign_id')->references('id')->on('runa_boundary.records')->restrictOnDelete()->restrictOnUpdate();
    }))->toThrow(RuntimeException::class);
    expect(DB::getSchemaBuilder()->hasColumn('app_boundary.records', 'foreign_id'))->toBeFalse();
});

it('rejects cross-module models relations through models pivots and polymorphic targets before SQL', function (): void {
    $record = new BoundaryRecord;
    expect($record->hasMany(BoundaryRecord::class)->getRelated()->getTable())->toBe('app_boundary.records');
    expect($record->belongsToMany(BoundaryRecord::class, 'app_boundary.links')->getTable())->toBe('app_boundary.links');
    foreach ([
        fn () => $record->hasMany(ForeignBoundaryRecord::class),
        fn () => $record->belongsTo(ForeignBoundaryRecord::class),
        fn () => $record->hasManyThrough(BoundaryRecord::class, ForeignBoundaryRecord::class),
        fn () => $record->belongsToMany(BoundaryRecord::class, 'runa_boundary.links'),
        fn () => $record->morphToMany(BoundaryRecord::class, 'tag', 'runa_boundary.links'),
        fn () => $record->morphTo('target'),
        fn () => $record->setTable('runa_boundary.records')->getTable(),
    ] as $attempt) {
        expect($attempt)->toThrow(RuntimeException::class);
    }
});

it('resolves sorted source-owned migration and seed files without executing them or checking enabled flags', function (): void {
    $directory = $this->sources->root.'/app/modules/boundary/database';
    mkdir($directory.'/migrations', 0775, true);
    mkdir($directory.'/seeders', 0775, true);
    $name = '2026_10_22_000001_app_boundary_records.php';
    file_put_contents($directory.'/migrations/'.$name, '<?php throw new RuntimeException("not executed");');
    file_put_contents($directory.'/seeders/Required.php', '<?php throw new RuntimeException("not executed");');
    $paths = new ModuleDataPaths($this->sources->root);
    $record = $this->registry->snapshot()->modules['app.boundary'];
    expect($record->enabled)->toBeFalse()->and($paths->migrations($record))->toBe([$directory.'/migrations/'.$name])
        ->and($paths->seeders($record))->toBe([$directory.'/seeders/Required.php']);
    rename($directory.'/migrations/'.$name, $directory.'/migrations/2026_10_22_000001_other.php');
    expect(fn () => $paths->migrations($record))->toThrow(RuntimeException::class);
    unlink($directory.'/migrations/2026_10_22_000001_other.php');
    symlink($directory.'/seeders/Required.php', $directory.'/migrations/'.$name);
    expect(fn () => $paths->migrations($record))->toThrow(InvalidArgumentException::class);
    unlink($directory.'/migrations/'.$name);
    file_put_contents($this->sources->root.'/app/modules/boundary/module.json', "\n", FILE_APPEND);
    expect(fn () => $paths->seeders($record))->toThrow(RuntimeException::class, 'synchronization');
});

it('validates foreign public references only through the supplied contract and does not disguise API failure', function (): void {
    $lookup = new class implements PublicReferenceLookup
    {
        public array $calls = [];

        public bool $available = true;

        public bool $exists = true;

        public function exists(string $publicId): bool
        {
            $this->calls[] = $publicId;
            if (! $this->available) {
                throw new RuntimeException('Reference unavailable');
            }

            return $this->exists;
        }
    };
    $rule = new PublicReferenceRule($lookup);
    foreach ([123, '123', (string) Str::uuid(), 'bad'] as $invalid) {
        expect(Validator::make(['ref' => $invalid], ['ref' => ['required', $rule]])->fails())->toBeTrue();
    }
    expect($lookup->calls)->toBe([]);
    $id = (string) Str::uuid7();
    expect(Validator::make(['ref' => strtoupper($id)], ['ref' => ['required', $rule]])->passes())->toBeTrue()->and($lookup->calls)->toBe([$id]);
    $lookup->exists = false;
    expect(Validator::make(['ref' => $id], ['ref' => [$rule]])->fails())->toBeTrue();
    $lookup->available = false;
    expect(fn () => Validator::make(['ref' => $id], ['ref' => [$rule]])->passes())->toThrow(RuntimeException::class);
});

it('preserves tombstoned Runa module data during an application wipe and rejects uncertain ownership', function (): void {
    $this->schema->create('records', fn (Blueprint $table) => $table->text('name'));
    (new ModuleSchema(DB::connection(), new ModuleIdentity('runa.boundary')))->create('records', fn (Blueprint $table) => $table->text('name'));
    DB::table('runa_boundary.records')->insert(['public_id' => (string) Str::uuid7(), 'name' => 'retained']);
    DB::table('shared.modules')->where('module_id', 'runa.boundary')->update(['registered' => false, 'enabled' => false]);
    (new DatabaseScope(base_path()))->wipe(DB::connection());
    expect(DB::table('runa_boundary.records')->value('name'))->toBe('retained')
        ->and(DB::getSchemaBuilder()->hasTable('app_boundary.records'))->toBeFalse()
        ->and(DB::getSchemaBuilder()->hasTable('shared.modules'))->toBeTrue();
    DB::statement("COMMENT ON SCHEMA runa_boundary IS 'unknown'");
    expect(fn () => (new DatabaseScope(base_path()))->wipe(DB::connection()))->toThrow(RuntimeException::class, 'ownership');
});
