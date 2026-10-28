<?php

namespace Runa\Modules\Audit\Queries;

use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\ModuleFramework\Api\PrincipalScope;
use Runa\Modules\Audit\Contracts\AuditEntity;
use Runa\Modules\Audit\Contracts\OwnHistory;
use Runa\Support\Modules\Api\OperationRejected;

final readonly class ReadOwnHistory implements OwnHistory
{
    public function __construct(private DatabaseManager $database, private PrincipalScope $principals) {}

    /** @param array<array-key, mixed> $actions */
    public function page(string $module, string $entityType, array $actions, ?string $before = null): array
    {
        $principal = $this->principals->current();
        if ($principal === null || $principal->kind !== 'human') {
            throw new OperationRejected('authentication_required', 'runa.audit', 'audit.own_history', 401);
        }
        $entity = new AuditEntity($module, $entityType, $principal->publicId);
        if (! array_is_list($actions) || count($actions) < 1 || count($actions) > 64) {
            throw new \InvalidArgumentException('Bounded history actions are required.');
        }
        foreach ($actions as $action) {
            if (! is_string($action)) {
                throw new \InvalidArgumentException('Invalid history action.');
            }
            new ApiOperation($action);
        }
        $db = $this->database->connection();
        $query = $db->table('runa_audit.events')->where('module', $module)->whereIn('action', $actions)
            ->whereExists(fn (Builder $q) => $q->selectRaw('1')->from('runa_audit.entities')->whereColumn('event_id', 'runa_audit.events.id')
                ->where('module', $entity->module)->where('entity_type', $entity->type)->where('entity_public_id', $entity->publicId));
        if ($before !== null) {
            new TrustedPrincipal($before);
            $id = (clone $query)->where('public_id', $before)->value('id');
            if ($id === null) {
                throw new OperationRejected('history_cursor_invalid', 'runa.audit', 'audit.own_history', 422);
            }
            $query->where('id', '<', $id);
        }
        /** @var list<\stdClass&object{public_id: string, occurred_at: string, action: string, outcome: string}> $rows */
        $rows = $query->orderByDesc('id')->limit(51)->get(['public_id', 'occurred_at', 'action', 'outcome'])->all();
        $more = count($rows) > 50;
        $rows = array_slice($rows, 0, 50);

        return ['entries' => array_map(static fn ($r) => ['public_id' => $r->public_id, 'occurred_at' => $r->occurred_at, 'action' => $r->action, 'outcome' => $r->outcome], $rows), 'next' => $more && $rows !== [] ? $rows[array_key_last($rows)]->public_id : null];
    }
}
