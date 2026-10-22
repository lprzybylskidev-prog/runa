<?php

namespace Runa\Support\Modules\Data;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Runa\Support\Modules\ModuleIdentity;
use RuntimeException;

/** Base for exposed module domain entities; technical tables may use ordinary models. */
abstract class ModuleModel extends Model
{
    use SoftDeletes;

    protected $hidden = ['id'];

    abstract public static function moduleId(): string;

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (ModuleModel $model): void {
            $model->setAttribute('public_id', $model->getAttribute('public_id') ?? (string) Str::uuid7());
        });
    }

    public function getTable(): string
    {
        $module = new ModuleIdentity(static::moduleId());
        $table = parent::getTable();
        if (! str_starts_with($table, $module->schema().'.')) {
            throw new RuntimeException('Module models require an explicitly owned schema-qualified table.');
        }
        $module->table(substr($table, strlen($module->schema()) + 1));

        return $table;
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        if (($field !== null && $field !== 'public_id') || ! PublicReferenceRule::valid($value)) {
            throw new RuntimeException('Module routes accept only UUIDv7 public references.');
        }

        return parent::resolveRouteBindingQuery($query, $value, 'public_id');
    }

    protected function newRelatedInstance($class)
    {
        $related = parent::newRelatedInstance($class);
        $this->assertRelated($related);

        return $related;
    }

    protected function newRelatedThroughInstance($class)
    {
        $related = parent::newRelatedThroughInstance($class);
        $this->assertRelated($related);

        return $related;
    }

    protected function newBelongsToMany($query, Model $parent, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relationName = null)
    {
        $this->assertPivot($table);

        return parent::newBelongsToMany($query, $parent, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relationName);
    }

    protected function newMorphToMany($query, Model $parent, $name, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relationName = null, $inverse = false)
    {
        $this->assertPivot($table);

        return parent::newMorphToMany($query, $parent, $name, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relationName, $inverse);
    }

    public function morphTo($name = null, $type = null, $id = null, $ownerKey = null)
    {
        // Eager morphTo instantiates database-selected classes outside newRelatedInstance.
        throw new RuntimeException('Open polymorphic targets are unsupported for module models; use explicit owned relations.');
    }

    private function assertRelated(Model $related): void
    {
        if (! $related instanceof self || $related::moduleId() !== static::moduleId() || $related->getConnectionName() !== $this->getConnectionName()) {
            throw new RuntimeException('Module relations must target an owned model on the same connection.');
        }
        $related->getTable();
    }

    private function assertPivot(string $table): void
    {
        $module = new ModuleIdentity(static::moduleId());
        if (! str_starts_with($table, $module->schema().'.')) {
            throw new RuntimeException('Module pivot tables must be explicitly schema-qualified and owned.');
        }
        $module->table(substr($table, strlen($module->schema()) + 1));
    }
}
