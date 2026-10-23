<?php

namespace Runa\Modules\Audit\Queries;

use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\Modules\Audit\Contracts\AuditActor;
use Runa\Modules\Audit\Contracts\AuditEntity;
use Runa\Modules\Audit\Contracts\AuditEntry;
use Runa\Modules\Audit\Contracts\AuditPage;
use Runa\Modules\Audit\Contracts\AuditQuery;
use Runa\Modules\Audit\Contracts\AuditReads;
use Runa\Modules\Audit\Contracts\AuditReference;

final readonly class ReadEvents implements AuditReads
{
    public function __construct(private DatabaseManager $database) {}

    public function page(AuditQuery $query): AuditPage
    {
        $builder = $this->events();
        foreach (['module' => $query->module, 'action' => $query->action, 'actor_public_id' => $query->actorPublicId,
            'actor_kind' => $query->actorKind, 'outcome' => $query->outcome, 'correlation_id' => $query->correlationId] as $column => $value) {
            if ($value !== null) {
                $builder->where($column, $value);
            }
        }
        if ($query->from !== null) {
            $builder->where('occurred_at', '>=', $query->from);
        }
        if ($query->until !== null) {
            $builder->where('occurred_at', '<=', $query->until);
        }
        if ($query->before !== null) {
            $cursor = $this->events()->where('public_id', $query->before)->value('id');
            if ($cursor === null) {
                throw new \InvalidArgumentException('Unknown Audit page cursor.');
            }
            $builder->where('id', '<', $cursor);
        }
        if ($query->entity !== null) {
            $entity = $query->entity;
            $builder->whereExists(fn (Builder $related) => $related->selectRaw('1')->from('runa_audit.entities')
                ->whereColumn('event_id', 'runa_audit.events.id')->where('module', $entity->module)
                ->where('entity_type', $entity->type)->where('entity_public_id', $entity->publicId));
        }
        // Summary selection never fetches potentially large change/context/execution trees.
        $rows = $builder->orderByDesc('id')->limit($query->limit + 1)->get([
            'id', 'public_id', 'occurred_at', 'module', 'action', 'outcome', 'actor_kind', 'actor_public_id', 'actor_label', 'correlation_id',
        ])->all();
        $more = count($rows) > $query->limit;
        $rows = array_slice($rows, 0, $query->limit);
        $entities = $this->entities(array_column($rows, 'id'));
        $entries = [];
        $bytes = 0;
        foreach ($rows as $row) {
            $entry = $this->entry($row, $entities[$row->id] ?? []);
            $size = strlen(json_encode($entry, JSON_THROW_ON_ERROR));
            if ($bytes + $size > 500000) {
                $more = true;
                break;
            }
            $entries[] = $entry;
            $bytes += $size;
        }

        return new AuditPage($entries, $more ? $entries[array_key_last($entries)]->reference->publicId : null);
    }

    public function detail(string $publicId): ?AuditEntry
    {
        new TrustedPrincipal($publicId);
        $row = $this->events()->where('public_id', $publicId)->first();

        return $row === null ? null : $this->entry($row, $this->entities([$row->id])[$row->id] ?? [], true);
    }

    public function metadata(): array
    {
        return ['version' => 1, 'order' => 'append_desc', 'max_page_size' => 100, 'max_page_bytes' => 524288, 'payloads' => 'detail_only',
            'filters' => ['module', 'action', 'actorPublicId', 'actorKind', 'entity', 'outcome', 'correlationId', 'from', 'until', 'before']];
    }

    private function events(): Builder
    {
        return $this->database->connection()->table('runa_audit.events');
    }

    private function entities(array $ids): array
    {
        $result = [];
        foreach ($this->database->connection()->table('runa_audit.entities')->whereIn('event_id', $ids)->orderBy('id')->get() as $entity) {
            $result[$entity->event_id][] = new AuditEntity($entity->module, $entity->entity_type, $entity->entity_public_id);
        }

        return $result;
    }

    private function entry(object $row, array $entities, bool $detail = false): AuditEntry
    {
        return new AuditEntry(new AuditReference($row->public_id, (new \DateTimeImmutable($row->occurred_at))->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.uP')),
            $row->module, $row->action, $row->outcome, new AuditActor($row->actor_kind, $row->actor_public_id, $row->actor_label),
            $row->correlation_id, $entities,
            $detail ? json_decode($row->changes, true, flags: JSON_THROW_ON_ERROR) : null,
            $detail ? json_decode($row->context, true, flags: JSON_THROW_ON_ERROR) : null,
            $detail ? json_decode($row->execution, true, flags: JSON_THROW_ON_ERROR) : null);
    }
}
