<?php

namespace Runa\Modules\Audit\Queries;

use Illuminate\Database\DatabaseManager;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\Modules\Audit\Contracts\AuditEntity;
use Runa\Modules\Audit\Contracts\OwnHistory;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Api\PrincipalScope;

final readonly class ReadOwnHistory implements OwnHistory
{
    public function __construct(private DatabaseManager $database, private PrincipalScope $principals) {}

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
            new ApiOperation($action);
        }
        $db = $this->database->connection();
        $query = $db->table('runa_audit.events')->where('module', $module)->whereIn('action', $actions)
            ->whereExists(fn ($q) => $q->selectRaw('1')->from('runa_audit.entities')->whereColumn('event_id', 'runa_audit.events.id')
                ->where('module', $entity->module)->where('entity_type', $entity->type)->where('entity_public_id', $entity->publicId));
        if ($before !== null) {
            new TrustedPrincipal($before);
            $id = (clone $query)->where('public_id', $before)->value('id');
            if ($id === null) {
                throw new OperationRejected('history_cursor_invalid', 'runa.audit', 'audit.own_history', 422);
            }
            $query->where('id', '<', $id);
        }
        $rows = $query->orderByDesc('id')->limit(51)->get(['public_id', 'occurred_at', 'action', 'outcome'])->all();
        $more = count($rows) > 50;
        $rows = array_slice($rows, 0, 50);

        return ['entries' => array_map(static fn ($r) => (array) $r, $rows), 'next' => $more ? end($rows)->public_id : null];
    }
}
