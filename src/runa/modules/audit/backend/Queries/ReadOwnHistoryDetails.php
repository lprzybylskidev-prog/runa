<?php

namespace Runa\Modules\Audit\Queries;

use Illuminate\Database\DatabaseManager;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\Modules\Audit\Contracts\AuditEntity;
use Runa\Modules\Audit\Contracts\OwnHistoryDetails;
use Runa\Modules\Audit\Support\SafePayload;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Api\PrincipalScope;

final readonly class ReadOwnHistoryDetails implements OwnHistoryDetails
{
    public function __construct(private DatabaseManager $database, private PrincipalScope $principals) {}

    public function detail(string $module, string $entityType, string $publicId, array $fieldsByAction): ?array
    {
        $principal = $this->principals->current();
        if ($principal === null || $principal->kind !== 'human') {
            throw new OperationRejected('authentication_required', 'runa.audit', 'audit.own_history_detail', 401);
        }
        new AuditEntity($module, $entityType, $principal->publicId);
        new TrustedPrincipal($publicId);
        if (count($fieldsByAction) < 1 || count($fieldsByAction) > 64) {
            throw new \InvalidArgumentException('Bounded action fields are required.');
        }
        foreach ($fieldsByAction as $action => $fields) {
            new ApiOperation($action);
            if (! array_is_list($fields) || count($fields) > 32) {
                throw new \InvalidArgumentException('Bounded history fields are required.');
            }
            foreach ($fields as $field) {
                if (! is_string($field) || preg_match('/\A[a-z][a-z0-9_]{0,63}\z/D', $field) !== 1 || SafePayload::secretName($field)) {
                    throw new \InvalidArgumentException('Unsafe history field.');
                }
            }
        }
        $row = $this->database->connection()->table('runa_audit.events')->where('public_id', $publicId)
            ->where('module', $module)->whereIn('action', array_keys($fieldsByAction))
            ->whereExists(fn ($q) => $q->selectRaw('1')->from('runa_audit.entities')->whereColumn('event_id', 'runa_audit.events.id')
                ->where('module', $module)->where('entity_type', $entityType)->where('entity_public_id', $principal->publicId))
            ->first(['public_id', 'occurred_at', 'action', 'outcome', 'changes']);
        if ($row === null) {
            return null;
        }
        $stored = SafePayload::clean(json_decode($row->changes, true, flags: JSON_THROW_ON_ERROR));
        $changes = [];
        foreach ($fieldsByAction[$row->action] as $field) {
            $pair = $stored[$field] ?? null;
            if (is_array($pair) && array_key_exists('old', $pair) && array_key_exists('new', $pair)
                && $this->safeValue($pair['old']) && $this->safeValue($pair['new'])) {
                $changes[] = ['field' => $field, 'before' => $pair['old'], 'after' => $pair['new']];
            }
        }

        return ['public_id' => $row->public_id, 'occurred_at' => $row->occurred_at, 'action' => $row->action,
            'outcome' => $row->outcome, 'changes' => $changes];
    }

    private function safeValue(mixed $value): bool
    {
        if (is_array($value)) {
            return array_is_list($value) && count($value) <= 21 && array_all($value, fn ($v) => ! is_array($v) && $this->safeValue($v));
        }

        return $value === null || is_bool($value) || is_int($value)
            || (is_string($value) && mb_strlen($value) <= 512);
    }
}
