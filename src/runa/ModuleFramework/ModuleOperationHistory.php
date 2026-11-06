<?php

namespace Runa\ModuleFramework;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use InvalidArgumentException;

/** Framework-owned metadata read for trusted integration; never selects recovery payloads. */
final readonly class ModuleOperationHistory
{
    public function __construct(private Connection $connection) {}

    /**
     * @param list<string> $intents
     * @return array{operations: list<array<string,mixed>>, next_cursor: ?string} */
    public function read(string $module, array $intents, ?string $cursor = null, int $limit = 25): array
    {
        if ($limit < 1 || $limit > 100 || $intents === [] || array_diff($intents, ['enable', 'disable', 'unregister', 'settings']) !== []) {
            throw new InvalidArgumentException('Invalid module history scope.');
        }
        $query = $this->connection->table('shared.module_transitions')
            ->whereIn('intent', $intents)
            ->where(fn (Builder $query): Builder => $query->where('target', $module)->orWhereJsonContains('affected', $module));
        if ($cursor !== null) {
            $query->where('public_id', '<', $cursor);
        }
        /** @var list<object{public_id:string,target:string,intent:string,status:string,created_at:string,updated_at:string}> $rows */
        $rows = $query->orderByDesc('public_id')->limit($limit + 1)
            ->get(['public_id', 'target', 'intent', 'status', 'created_at', 'updated_at'])->all();
        $more = count($rows) > $limit;
        $rows = array_slice($rows, 0, $limit);
        $operations = [];
        foreach ($rows as $row) {
            $kind = $row->intent === 'settings' ? 'settings' : 'modules';
            $actions = [$kind.'.status'];
            if (in_array($row->status, ModuleRegistry::ACTIVE_TRANSITIONS, true)) {
                array_push($actions, ...($kind === 'settings' ? ['settings.recover'] : ['modules.resume', 'modules.cancel']));
            }
            $date = static fn (string $value): string => (new DateTimeImmutable($value))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');

            $operations[] = ['public_id' => $row->public_id, 'target' => $row->target, 'intent' => $row->intent,
                'status' => $row->status, 'created_at' => $date($row->created_at), 'updated_at' => $date($row->updated_at), 'actions' => $actions];
        }
        $last = $rows === [] ? null : $rows[count($rows) - 1]->public_id;

        return ['operations' => $operations, 'next_cursor' => $more ? $last : null];
    }
}
