<?php

namespace Runa\Modules\Audit\Support;

use Closure;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Runa\Contracts\Modules\ApiOperation;
use Runa\ModuleFramework\Api\OperationGuard;
use Runa\Modules\Audit\Contracts\AuditEntity;
use Runa\Modules\Audit\Contracts\AuditEvent;
use Runa\Modules\Audit\Contracts\AuditModelPolicy;
use Runa\Modules\Audit\Contracts\AuditRecords;
use Runa\Modules\Audit\Contracts\AuditSuppression;
use Runa\Modules\Audit\Contracts\AuditUnavailable;
use Runa\Support\Modules\Api\ModuleApis;

final readonly class AutomaticCapture
{
    public function __construct(private DatabaseManager $database, private ModuleApis $apis, private AuditSuppression $suppression, private OperationGuard $guard) {}

    public function mutate(Model $model, AuditModelPolicy $policy, string $operation, Closure $write): mixed
    {
        $connection = $model->getConnection();
        if ($connection !== $this->database->connection() || $connection->getDriverName() !== 'pgsql' || $connection->transactionLevel() < 1) {
            throw new AuditUnavailable('Auditable mutation requires an existing default PostgreSQL transaction before writing.');
        }
        // Guard before the write, including disabled/suppressed events; no availability bypass.
        $api = $this->apis->resolve('runa.audit', AuditRecords::class, '1.0.0');
        $this->guard->enter('runa.audit', new ApiOperation('audit.record'));
        $attributes = $model->getAttributes();
        $original = $model->getRawOriginal();
        $exists = $model->exists;
        $recent = $model->wasRecentlyCreated;
        try {
            return $connection->transaction(function () use ($model, $policy, $operation, $write, $api): mixed {
                $soft = in_array(SoftDeletes::class, class_uses_recursive($model), true);
                $deletedColumn = $soft ? $model->getDeletedAtColumn() : null;
                $columns = array_values(array_unique([...$policy->fields, 'public_id', ...($soft ? [$deletedColumn] : [])]));
                $read = fn () => $model->getConnection()->table($model->getTable())
                    ->where($model->getKeyName(), $model->getRawOriginal($model->getKeyName()) ?? $model->getKey())
                    ->lockForUpdate()->first($columns);
                $before = $model->exists ? $read() : null;
                if ($model->exists && $before === null) {
                    throw new AuditUnavailable('Auditable target no longer exists.');
                }
                if ($before !== null && $model->getAttribute('public_id') !== $before->public_id) {
                    throw new AuditUnavailable('Auditable public identity cannot be replaced.');
                }
                $result = $write();
                if ($result === false || $result === null) {
                    return $result;
                }
                $after = $model->exists ? $read() : null;
                $event = $operation === 'delete' ? ($after === null ? 'force_deleted' : 'deleted')
                    : ($before === null ? 'created' : ($soft && $before->$deletedColumn !== null && $after?->$deletedColumn === null ? 'restored' : 'updated'));
                if ($operation === 'delete' && ! $soft) {
                    $event = 'deleted';
                }
                if (! in_array($event, $policy->events, true) || $this->suppression->active()) {
                    return $result;
                }
                $old = $this->approved($before, $policy);
                $new = $this->approved($after, $policy);
                $changes = [];
                foreach ($policy->fields as $field) {
                    if (($old[$field] ?? null) !== ($new[$field] ?? null)) {
                        $changes[$field] = ['old' => $old[$field] ?? null, 'new' => $new[$field] ?? null];
                    }
                }
                if ($event === 'updated' && $changes === []) {
                    return $result;
                }
                $api->record(new AuditEvent($policy->module, $policy->type.'.'.$event,
                    entities: [new AuditEntity($policy->module, $policy->type, ($after ?? $before)->public_id)],
                    changes: $policy->snapshot ? ['before' => $old, 'after' => $new] : $changes,
                    context: ['capture' => $policy->snapshot ? 'snapshot' : 'diff'], sensitiveFields: $policy->sensitiveFields));

                return $result;
            });
        } catch (\Throwable $error) {
            // A caught Audit failure must not leave a committed write or a falsely saved model.
            $model->setRawAttributes($original, true);
            $model->setRawAttributes($attributes);
            $model->exists = $exists;
            $model->wasRecentlyCreated = $recent;
            throw $error;
        }
    }

    private function approved(?object $row, AuditModelPolicy $policy): ?array
    {
        if ($row === null) {
            return null;
        }
        $values = [];
        foreach ($policy->fields as $field) {
            $value = $row->$field;
            // Decode structured JSON before redaction, never hide nested secrets in an opaque string.
            if (is_string($value) && in_array(substr(ltrim($value), 0, 1), ['{', '['], true)) {
                $decoded = json_decode($value, true, 16);
                if (json_last_error() === JSON_ERROR_DEPTH) {
                    throw new \InvalidArgumentException('Audit structured attribute exceeds nesting limits.');
                }
                if (json_last_error() === JSON_ERROR_NONE) {
                    $value = $decoded;
                }
            }
            $values[$field] = $value;
        }

        return SafePayload::clean($values, $policy->sensitiveFields);
    }
}
