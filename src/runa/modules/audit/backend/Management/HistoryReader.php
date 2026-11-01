<?php

namespace Runa\Modules\Audit\Management;

use Runa\Contracts\Modules\ManagementPageResult;
use Runa\Contracts\Modules\ManagementRead;
use Runa\Contracts\Modules\ManagementRecord;
use Runa\Contracts\Modules\ManagementResourceReader;
use Runa\Modules\Audit\Contracts\AuditEntity;
use Runa\Modules\Audit\Contracts\AuditEntry;
use Runa\Modules\Audit\Contracts\AuditQuery;
use Runa\Modules\Audit\Contracts\AuditReads;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationRejected;

/** No foreign lookups, mutable event API, inferred count or summary payload projection. */
final readonly class HistoryReader implements ManagementResourceReader
{
    public function __construct(private ModuleApis $apis) {}

    public function read(ManagementRead $input): ManagementRecord
    {
        if ($input->publicId === null) {
            return new ManagementRecord(null, 'empty', ['payload_part' => null, 'payload_next' => null]);
        }
        $entry = $this->api()->detail($input->publicId) ?? throw new OperationRejected('audit_event_missing', 'runa.audit', 'history.detail', 404);
        if ($input->cursor !== null && preg_match('/\A[0-9]{1,3}\z/D', $input->cursor) !== 1) {
            throw new OperationRejected('admin_input_invalid', 'runa.audit', 'history.detail', 422);
        }
        $part = $input->cursor === null ? 0 : (int) $input->cursor;
        $payload = [];
        $parts = 1;
        foreach (['changes' => $entry->changes, 'context' => $entry->context, 'execution' => $entry->execution] as $key => $value) {
            // json_encode produces ASCII escapes, so byte segments never split UTF-8.
            // Keep the existing 64-KiB management-record bound even for full native payloads.
            $chunks = str_split(json_encode($value, JSON_THROW_ON_ERROR), 4096);
            $parts = max($parts, count($chunks));
            $payload[$key] = $chunks[$part] ?? '';
        }
        if ($part >= $parts) {
            throw new OperationRejected('admin_input_invalid', 'runa.audit', 'history.detail', 422);
        }
        $summary = $this->record($entry);

        return new ManagementRecord($summary->publicId, $summary->revision, $summary->values + $payload + [
            'entities' => json_encode(array_map(fn ($entity) => ['module' => $entity->module, 'type' => $entity->type, 'public_id' => $entity->publicId], $entry->entities), JSON_THROW_ON_ERROR),
            'payload_part' => ($part + 1).' / '.$parts,
            'payload_next' => $part + 1 < $parts ? (string) ($part + 1) : null,
        ]);
    }

    public function page(ManagementRead $input): ManagementPageResult
    {
        try {
            $f = $input->filters;
            if ($input->publicId !== null || $input->sort !== [] || array_diff(array_keys($f), ['module', 'action', 'actor_public_id', 'actor_kind', 'outcome', 'correlation_id', 'entity_module', 'entity_type', 'entity_public_id', 'from', 'until']) !== []) {
                throw new \InvalidArgumentException('Invalid history filters.');
            }
            $entity = array_intersect_key($f, array_flip(['entity_module', 'entity_type', 'entity_public_id']));
            if ($entity !== [] && count($entity) !== 3) {
                throw new \InvalidArgumentException('Complete entity reference required.');
            }
            $query = new AuditQuery($f['module'] ?? null, $input->limit, $f['action'] ?? null, $f['actor_public_id'] ?? null, $f['actor_kind'] ?? null,
                $entity === [] ? null : new AuditEntity($f['entity_module'], $f['entity_type'], $f['entity_public_id']),
                $f['outcome'] ?? null, $f['correlation_id'] ?? null, $f['from'] ?? null, $f['until'] ?? null, $input->cursor);
        } catch (\InvalidArgumentException|\TypeError) {
            throw new OperationRejected('admin_input_invalid', 'runa.audit', 'history.page', 422);
        }
        $page = $this->api()->page($query);

        return new ManagementPageResult(array_map($this->record(...), $page->entries), $page->nextBefore, $page->total);
    }

    private function record(AuditEntry $entry): ManagementRecord
    {
        return new ManagementRecord($entry->reference->publicId, $entry->reference->publicId, [
            'occurred_at' => $entry->reference->occurredAt, 'module' => $entry->module, 'action' => $entry->action, 'outcome' => $entry->outcome,
            'actor_public_id' => $entry->actor->publicId, 'actor_kind' => $entry->actor->kind, 'actor_label' => $entry->actor->label, 'correlation_id' => $entry->correlationId,
        ]);
    }

    private function api(): AuditReads
    {
        return $this->apis->resolve('runa.audit', AuditReads::class, '1.0.0');
    }
}
