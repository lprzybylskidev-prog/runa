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

/** @phpstan-type EventRow \stdClass&object{id: int, public_id: string, occurred_at: string, module: string, action: string, outcome: string, actor_kind: string, actor_public_id: string|null, actor_label: string|null, correlation_id: string|null} */
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
        /** @var list<EventRow> $rows */
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

        return new AuditPage($entries, $more && $entries !== [] ? $entries[array_key_last($entries)]->reference->publicId : null);
    }

    public function detail(string $publicId): ?AuditEntry
    {
        new TrustedPrincipal($publicId);
        /** @var EventRow|null $row */
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

    /** @param list<int> $ids
     * @return array<int, list<AuditEntity>>
     */
    private function entities(array $ids): array
    {
        $result = [];
        /** @var \stdClass&object{event_id: int, module: string, entity_type: string, entity_public_id: string} $entity */
        foreach ($this->database->connection()->table('runa_audit.entities')->whereIn('event_id', $ids)->orderBy('id')->get() as $entity) {
            $result[$entity->event_id][] = new AuditEntity($entity->module, $entity->entity_type, $entity->entity_public_id);
        }

        return $result;
    }

    /** @param EventRow $row
     * @param  list<AuditEntity>  $entities
     */
    private function entry(object $row, array $entities, bool $detail = false): AuditEntry
    {
        return new AuditEntry(new AuditReference($row->public_id, (new \DateTimeImmutable($row->occurred_at))->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.uP')),
            $row->module, $row->action, $row->outcome, new AuditActor($row->actor_kind, $row->actor_public_id, $row->actor_label),
            $row->correlation_id, $entities,
            $detail ? $this->payload($row->changes) : null,
            $detail ? $this->payload($row->context) : null,
            $detail ? $this->payload($row->execution) : null);
    }

    /** @return array<array-key, mixed> */
    private function payload(mixed $json): array
    {
        if (! is_string($json)) {
            throw new \RuntimeException('Invalid stored Audit payload.');
        }
        $value = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($value)) {
            throw new \RuntimeException('Invalid stored Audit payload.');
        }

        return $value;
    }
}
