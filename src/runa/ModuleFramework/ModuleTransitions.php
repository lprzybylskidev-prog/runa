<?php

namespace Runa\ModuleFramework;

use Closure;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Database\Connection;
use Illuminate\Support\Str;
use Runa\Contracts\Modules\AdministrativePreview;
use Runa\Contracts\Modules\LifecyclePlan;
use Runa\Contracts\Modules\TransitionContext;
use Runa\Contracts\Modules\TransitionResult;
use Runa\Support\Modules\Work\ModuleWork;
use RuntimeException;
use Throwable;

/** Durable reservations surround reversible hooks; only short transactions publish registry state.
 * @phpstan-type Progress array{state: string, token: ?string, code: string}
 * @phpstan-type Payload array{plans: array<string, array{revision: string, blocked: bool, code: string}>, changes: array<string, bool>, progress: array<string, Progress>, terminal: string}
 * @phpstan-type Operation object{public_id: string, intent: string, target: string, generation: int, affected: string, payload: string, status: string, publication_generation: ?int}
 */
final class ModuleTransitions
{
    private static bool $running = false;

    private ?int $runnerPid = null;

    public function __construct(
        private readonly Connection $connection,
        private readonly ModuleRegistry $registry,
        private readonly LifecyclePolicies $policies,
        private readonly Encrypter $encrypter,
        private readonly string $platformVersion,
        private readonly ModuleHealth $health,
    ) {}

    public function preview(string $intent, string $target): AdministrativePreview
    {
        $snapshot = $this->registry->snapshot();
        $graph = (new ModuleGraph($snapshot, $this->platformVersion))->preview($intent, $target);
        $plans = [];
        $blockers = [];
        foreach ($graph->changes as $id => $enabled) {
            if ($intent === 'unregister') {
                continue;
            }
            try {
                $record = $snapshot->modules[$id];
                $policy = $this->policies->resolve($record);
                $plans[$id] = $policy->plan($intent);
                if ($plans[$id]->blocked) {
                    $blockers[$id] = $plans[$id]->code;
                }
                if ($enabled && ! $policy->acceptsDataVersion($record->installedVersion, $record->sourceVersion)) {
                    $blockers[$id] = 'installed_data_incompatible';
                }
            } catch (Throwable) {
                $blockers[$id] = 'policy_unavailable';
            }
        }
        // Unchanged required dependencies must also have compatible installed data.
        if ($intent === 'enable') {
            foreach ($graph->affected as $id) {
                if ($this->health->inspect($snapshot->modules[$id])['blocked']) {
                    $blockers[$id] ??= 'health_unavailable';
                }
                if (isset($graph->changes[$id])) {
                    continue;
                }
                try {
                    $record = $snapshot->modules[$id];
                    if (! $this->policies->resolve($record)->acceptsDataVersion($record->installedVersion, $record->sourceVersion)) {
                        $blockers[$id] = 'installed_data_incompatible';
                    }
                } catch (Throwable) {
                    $blockers[$id] = 'policy_unavailable';
                }
            }
        }

        $activeWork = [];
        /** @var list<object{module_key: string, status: string, total: int}> $workCounts */
        $workCounts = $this->connection->table('shared.module_work as w')->join('shared.modules as m', 'm.id', '=', 'w.module_id')
            ->whereIn('m.module_id', $graph->affected)->whereIn('w.status', ModuleWork::ACTIVE)
            ->selectRaw('m.module_id as module_key, w.status, count(*) as total')->groupBy('m.module_id', 'w.status')->get()->all();
        foreach ($workCounts as $count) {
            $activeWork[$count->module_key][$count->status] = $count->total;
        }

        return new AdministrativePreview($graph, $plans, $blockers, $activeWork);
    }

    public function apply(AdministrativePreview $preview): TransitionResult
    {
        return $this->exclusive(function () use ($preview): TransitionResult {
            if (! $preview->ready() || $preview != $this->preview($preview->graph->intent, $preview->graph->target)) {
                throw new RuntimeException('Blocked, stale or altered administrative preview.');
            }
            $id = (string) Str::uuid7();
            $this->connection->transaction(function () use ($preview, $id): void {
                $this->lock();
                $this->assertNoTransition();
                (new ModuleGraph($this->registry->snapshot(), $this->platformVersion))->assertCurrent($preview->graph);
                $this->connection->table('shared.module_transitions')->insert([
                    'public_id' => $id, 'intent' => $preview->graph->intent, 'target' => $preview->graph->target,
                    'generation' => $preview->graph->generation, 'affected' => json_encode($preview->graph->affected, JSON_THROW_ON_ERROR),
                    'payload' => $this->encode(['plans' => array_map(self::planState(...), $preview->plans), 'changes' => $preview->graph->changes, 'progress' => [], 'terminal' => 'failed']),
                    'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
                ]);
                if (in_array($preview->graph->intent, ['disable', 'unregister'], true)) {
                    $this->connection->table('shared.module_work')->whereIn('module_id', $this->connection->table('shared.modules')->whereIn('module_id', $preview->graph->affected)->select('id'))
                        ->whereIn('status', ModuleWork::ACTIVE)->update(['cancel_requested' => true, 'updated_at' => now()]);
                }
                $this->bump(); // Publish fence and cooperative work signals with unchanged flags.
            });

            return $this->advance($id);
        });
    }

    /** Resume waiting work; interrupted calls reconcile through compensation, never blind replay. */
    public function resume(string $operationId): TransitionResult
    {
        return $this->exclusive(fn (): TransitionResult => $this->advance($operationId));
    }

    public function cancel(string $operationId): TransitionResult
    {
        return $this->exclusive(function () use ($operationId): TransitionResult {
            $row = $this->operation($operationId);
            if (! in_array($row->status, ModuleRegistry::ACTIVE_TRANSITIONS, true)) {
                return $this->status($operationId);
            }

            return $this->compensate($row, $this->decode($row->payload), 'failed');
        });
    }

    public function status(string $operationId): TransitionResult
    {
        $row = $this->operation($operationId);
        $payload = $this->decode($row->payload);
        $status = in_array($row->status, ['preparing', 'compensating'], true) ? 'pending' : $row->status;

        return new TransitionResult($row->public_id, $status, $row->publication_generation, array_map(fn (array $progress): string => $progress['code'], $payload['progress']));
    }

    private function advance(string $id): TransitionResult
    {
        $row = $this->operation($id);
        if (! in_array($row->status, ModuleRegistry::ACTIVE_TRANSITIONS, true)) {
            return $this->status($id);
        }
        $payload = $this->decode($row->payload);
        if ($row->status !== 'pending') {
            return $this->compensate($row, $payload, $payload['terminal']);
        }
        try {
            $this->assertPreparationCurrent($row, $payload);
            foreach ($payload['changes'] as $module => $enabled) {
                if ($row->intent === 'unregister') {
                    continue;
                }
                if (($payload['progress'][$module]['state'] ?? null) === 'ready') {
                    continue;
                }
                $token = $payload['progress'][$module]['token'] ?? null;
                $payload['progress'][$module] = ['state' => 'preparing', 'token' => $token, 'code' => 'preparing'];
                $this->save($id, 'preparing', $payload); // Durable intent before a possibly interrupted hook.
                $policy = $this->policies->resolve($this->registry->snapshot()->modules[$module]);
                $this->assertRunner();
                $result = $policy->prepare($this->context($row, $module, $payload), $token);
                $payload['progress'][$module] = ['state' => $result->state, 'token' => $result->token, 'code' => $result->code];
                $this->save($id, 'preparing', $payload);
                if ($result->state === 'refused') {
                    return $this->compensate($row, $payload, 'refused');
                }
                if ($result->state === 'waiting') {
                    $this->save($id, 'pending', $payload);

                    return $this->status($id);
                }
            }
            $this->assertPreparationCurrent($row, $payload);
            $this->connection->transaction(function () use ($row, $payload): void {
                $this->assertRunner();
                $this->lock();
                $snapshot = $this->registry->snapshot();
                if ($snapshot->generation !== $row->generation + 1) {
                    throw new RuntimeException('Transition reservation is stale.');
                }
                $graph = (new ModuleGraph($snapshot, $this->platformVersion))->preview($row->intent, $row->target);
                if (! $graph->graphReady() || $graph->changes !== $payload['changes']) {
                    throw new RuntimeException('Transition graph changed.');
                }
                foreach ($payload['changes'] as $module => $enabled) {
                    $values = $row->intent === 'unregister' ? ['registered' => false, 'enabled' => false] : ['enabled' => $enabled];
                    $this->connection->table('shared.modules')->where('module_id', $module)->update([...$values, 'updated_at' => now()]);
                }
                $this->bump();
                foreach ($payload['progress'] as &$progress) {
                    $progress['token'] = null;
                }
                unset($progress);
                $this->save($row->public_id, 'succeeded', $payload);
                $this->connection->table('shared.module_transitions')->where('public_id', $row->public_id)->update([
                    'status' => 'succeeded', 'publication_generation' => $snapshot->generation + 1, 'updated_at' => now(),
                ]);
            });
        } catch (Throwable) {
            // If publication committed before the client lost its acknowledgement, it is success.
            if ($this->operation($id)->status === 'succeeded') {
                return $this->status($id);
            }

            return $this->compensate($row, $payload, 'failed');
        }

        return $this->status($id);
    }

    /** @param Operation $row
     * @param  Payload  $payload
     */
    private function assertPreparationCurrent(object $row, array $payload): void
    {
        $current = $this->preview($row->intent, $row->target);
        if (! $current->ready() || $current->graph->generation !== $row->generation + 1 || $current->graph->changes !== $payload['changes'] || array_map(self::planState(...), $current->plans) !== $payload['plans']) {
            throw new RuntimeException('Module preparation revision changed.');
        }
    }

    /** @param Operation $row
     * @param  Payload  $payload
     */
    private function compensate(object $row, array $payload, string $terminal): TransitionResult
    {
        $this->assertRunner();
        $payload['terminal'] = $terminal;
        $this->save($row->public_id, 'compensating', $payload);
        foreach (array_reverse(array_keys($payload['progress'])) as $module) {
            if ($payload['progress'][$module]['state'] === 'compensated') {
                continue;
            }
            try {
                $policy = $this->policies->resolve($this->registry->snapshot()->modules[$module]);
                $this->assertRunner();
                if (! $policy->compensate($this->context($row, $module, $payload), $payload['progress'][$module]['token'])) {
                    throw new RuntimeException('Compensation needs recovery.');
                }
                if ($payload['progress'][$module]['state'] === 'preparing') {
                    $payload['progress'][$module]['code'] = 'preparation_interrupted';
                }
                $payload['progress'][$module]['state'] = 'compensated';
                $payload['progress'][$module]['token'] = null;
                $this->save($row->public_id, 'compensating', $payload);
            } catch (Throwable) {
                $payload['progress'][$module]['code'] = 'compensation_required';
            }
        }
        $complete = count(array_filter($payload['progress'], fn (array $progress): bool => $progress['state'] !== 'compensated')) === 0;
        $this->connection->transaction(function () use ($row, $payload, $terminal, $complete): void {
            $this->lock();
            $this->save($row->public_id, $complete ? $terminal : 'recovery_required', $payload);
            if ($complete) {
                $this->bump(); // Release the fence, preserving all original flags.
            }
        });

        return $this->status($row->public_id);
    }

    /** @param Operation $row
     * @param  Payload  $payload
     */
    private function context(object $row, string $module, array $payload): TransitionContext
    {
        $plan = $payload['plans'][$module];

        return new TransitionContext($row->public_id, $module, $row->intent, $row->generation, new LifecyclePlan($plan['revision'], $plan['blocked'], $plan['code']));
    }

    /** @return Operation */
    private function operation(string $id): object
    {
        /** @var Operation|null $row */
        $row = $this->connection->table('shared.module_transitions')->where('public_id', $id)->where('intent', '<>', 'settings')->first();
        if ($row === null) {
            throw new RuntimeException('Unknown module transition.');
        }

        return $row;
    }

    /** @param Payload $payload */
    private function save(string $id, string $status, array $payload): void
    {
        $this->assertRunner();
        $this->connection->table('shared.module_transitions')->where('public_id', $id)->update(['status' => $status, 'payload' => $this->encode($payload), 'updated_at' => now()]);
    }

    /** @param Payload $payload */
    private function encode(array $payload): string
    {
        return $this->encrypter->encrypt(json_encode($payload, JSON_THROW_ON_ERROR), false);
    }

    /** @return Payload */
    private function decode(string $payload): array
    {
        $json = $this->encrypter->decrypt($payload, false);
        if (! is_string($json)) {
            throw new RuntimeException('Invalid encrypted transition payload.');
        }
        /** @var Payload $decoded */
        $decoded = json_decode($json, true, 32, JSON_THROW_ON_ERROR);

        return $decoded;
    }

    private function lock(): void
    {
        $this->connection->table('shared.module_registry_state')->where('id', 1)->lockForUpdate()->first();
        if ($this->connection->table('shared.module_registry_state')->where('id', 1)->value('update_operation') !== null) {
            throw new RuntimeException('A module update requires completion or recovery.');
        }
    }

    private function bump(): void
    {
        $this->connection->table('shared.module_registry_state')->where('id', 1)->increment('generation');
    }

    private function assertNoTransition(): void
    {
        if ($this->connection->table('shared.module_transitions')->whereIn('status', ModuleRegistry::ACTIVE_TRANSITIONS)->exists()) {
            throw new RuntimeException('A module transition requires completion or recovery.');
        }
    }

    /** @return array{revision: string, blocked: bool, code: string} */
    private static function planState(LifecyclePlan $plan): array
    {
        return ['revision' => $plan->revision, 'blocked' => $plan->blocked, 'code' => $plan->code];
    }

    private function assertRunner(): void
    {
        /** @var object{pid: int} $session */
        $session = $this->connection->selectOne('SELECT pg_backend_pid() AS pid');
        if ($this->runnerPid === null || $session->pid !== $this->runnerPid) {
            throw new RuntimeException('Transition runner connection changed; reconcile the durable operation with a new runner.');
        }
    }

    /** Session lock serializes runners across hooks without holding a DB transaction open.
     * Process death releases it, leaving durable progress for conservative recovery.
     *
     * @param  Closure(): TransitionResult  $work
     */
    private function exclusive(Closure $work): TransitionResult
    {
        if (self::$running || $this->connection->transactionLevel() !== 0) {
            throw new RuntimeException('Module transitions require an independent runner outside a transaction.');
        }
        /** @var object{acquired: bool, pid: int} $lock */
        $lock = $this->connection->selectOne('SELECT pg_try_advisory_lock(1381322305, 4) AS acquired, pg_backend_pid() AS pid');
        if (! $lock->acquired) {
            throw new RuntimeException('Another module transition runner is active.');
        }
        self::$running = true;
        $this->runnerPid = $lock->pid;
        try {
            return $work();
        } finally {
            self::$running = false;
            $this->runnerPid = null;
            $this->connection->selectOne('SELECT pg_advisory_unlock(1381322305, 4)');
        }
    }
}
