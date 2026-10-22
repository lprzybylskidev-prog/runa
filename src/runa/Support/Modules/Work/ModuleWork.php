<?php

namespace Runa\Support\Modules\Work;

use Closure;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Queue\Factory;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\JobDefinition;
use Runa\Contracts\Modules\OperationContext;
use Runa\Contracts\Modules\PublicEvent;
use Runa\Contracts\Modules\WorkDecision;
use Runa\Contracts\Modules\WorkStatus;
use Runa\Support\Modules\Api\BoundaryValues;
use Runa\Support\Modules\Api\OperationGuard;
use Runa\Support\Modules\Api\OperationScope;
use Runa\Support\Modules\Events\EventSubscriptions;
use Runa\Support\Modules\ModuleRegistry;
use RuntimeException;
use Throwable;

/** Durable execution protocol, never a business cancellation/compensation policy.
 * @phpstan-type WorkRow object{public_id: string, module_key: string, operation: string, manifest_hash: string, correlation_id: string, origin_module: ?string, generation: int, payload: string, status: string, attempts: int, cancel_requested: bool, lease_token: ?string, lease_until: ?string, available_at: string, progress: string, event_listener: ?string, delivery_key: ?string}
 */
final readonly class ModuleWork
{
    public const ACTIVE = ['pending', 'running', 'failed', 'recovery_required'];

    public function __construct(private Connection $connection, private ModuleRegistry $registry, private WorkDefinitions $definitions, private OperationGuard $guard, private OperationScope $scope, private Encrypter $encrypter, private Factory $queues, private ?EventSubscriptions $subscriptions = null) {}

    /** Database queue is the managed durable transport. Insert work and envelope atomically.
     * @param  array<array-key, mixed>  $payload
     */
    public function dispatch(string $module, string $operation, array $payload): string
    {
        return $this->prepareDispatch($module, $operation, $payload)();
    }

    /** Admit outside a business transaction, then invoke once within it. Generation must still match.
     * @param  array<array-key, mixed>  $payload
     * @return Closure(): string
     */
    public function prepareDispatch(string $module, string $operation, array $payload, ?string $eventListener = null): Closure
    {
        if ($eventListener === null) {
            $this->subscriptions?->assertOrdinaryJob($module, $operation);
        }
        $event = $eventListener === null ? null : PublicEvent::fromArray($payload);
        $deliveryKey = null;
        if ($event !== null) {
            $listener = $this->subscriptions?->resolve($module, $eventListener, $event);
            if ($listener === null || $listener->mode !== 'async' || $listener->target !== $operation) {
                throw new RuntimeException('Invalid async event binding.');
            }
            $deliveryKey = EventSubscriptions::key($module, $eventListener, $event);
        }
        [$record, $definition] = $this->definitions->resolve($module, $operation);
        $context = $this->guard->enter($module, new ApiOperation($operation, $definition->permission));
        if ($event !== null && $this->subscriptions->resolve($module, $eventListener, $event, false) != $listener) {
            throw new RuntimeException('Event admission changed.');
        }
        $this->guard->assertCurrent($context);
        BoundaryValues::check($payload, "\0");
        $json = json_encode($payload, JSON_THROW_ON_ERROR);
        if (strlen($json) > 65536 || config('queue.default') !== 'database' || config('queue.connections.database.driver') !== 'database'
            || config('queue.connections.database.connection') !== $this->connection->getName()
            || config()->integer('queue.connections.database.retry_after') <= $definition->leaseSeconds) {
            throw new RuntimeException('Managed module work requires bounded JSON and the atomic database queue with retry_after greater than the lease.');
        }
        $id = (string) Str::uuid7();

        return function () use ($id, $module, $operation, $json, $context, $record, $definition, $eventListener, $deliveryKey, $event): string {
            return $this->connection->transaction(function () use ($id, $module, $operation, $json, $context, $record, $definition, $eventListener, $deliveryKey, $event): string {
                $this->lock();
                $this->guard->assertCurrent($context);
                if ($deliveryKey !== null) {
                    $existing = $this->connection->table('shared.module_work')->where('delivery_key', $deliveryKey)->value('public_id');
                    if (is_string($existing)) {
                        if ($this->payload($this->row($existing)) !== json_decode($json, true, 64, JSON_THROW_ON_ERROR)) {
                            throw new RuntimeException('Event identity reused with a different envelope.');
                        }

                        return $existing;
                    }
                }
                $this->connection->table('shared.module_work')->insert([
                    'public_id' => $id, 'module_id' => $this->connection->table('shared.modules')->where('module_id', $module)->value('id'),
                    'operation' => $operation, 'manifest_hash' => $record->manifestHash, 'generation' => $context->generation,
                    'payload' => $this->encrypter->encrypt($json, false), 'correlation_id' => $event->correlationId ?? $context->correlationId, 'origin_module' => $event->module ?? $context->originModule,
                    'event_listener' => $eventListener, 'delivery_key' => $deliveryKey,
                    'status' => 'pending', 'available_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                ]);
                // queue.database after_commit delays this callback, so enqueue explicitly within this transaction.
                $queue = $this->queues->connection('database');
                $job = new ModuleJob($id, $definition->leaseSeconds - 5);
                $queue->push($job);

                return $id;
            });
        };
    }

    public function dispatchEvent(string $module, string $listener, PublicEvent $event): string
    {
        $definition = $this->subscriptions?->resolve($module, $listener, $event) ?? throw new RuntimeException('Event transport unavailable.');

        return $this->prepareDispatch($module, $definition->target, $event->toArray(), $listener)();
    }

    /** @param WorkRow $row
     * @return array<array-key, mixed>
     */
    private function payload(object $row): array
    {
        $json = $this->encrypter->decrypt($row->payload, false);
        if (! is_string($json)) {
            throw new RuntimeException('Invalid encrypted work payload.');
        }
        $payload = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
        if (! is_array($payload)) {
            throw new RuntimeException('Invalid work payload.');
        }

        return $payload;
    }

    /** @param WorkRow $row */
    private function eventAdmission(object $row, bool $checkAvailability = true): void
    {
        if ($row->event_listener === null) {
            return;
        }
        $event = PublicEvent::fromArray($this->payload($row));
        $listener = $this->subscriptions?->resolve($row->module_key, $row->event_listener, $event, $checkAvailability);
        if ($listener === null || $listener->mode !== 'async' || $listener->target !== $row->operation || $row->delivery_key !== EventSubscriptions::key($row->module_key, $row->event_listener, $event)) {
            throw new RuntimeException('Event delivery binding changed.');
        }
    }

    /** Return a bounded release delay, or null when this delivery is finished. */
    public function execute(string $id): ?int
    {
        if (! $this->query($id)->exists()) {
            return null; // An orphan diagnostic envelope has no executable work.
        }
        $row = $this->row($id);
        if ($row->status === 'running') {
            $this->expire($id);

            return null; // Never steal a live or crashed lease, or replay ambiguous effects.
        }
        if ($row->status !== 'pending') {
            return null;
        }
        if (Carbon::parse($row->available_at)->isFuture()) {
            return max(1, (int) ceil(now()->diffInSeconds(Carbon::parse($row->available_at))));
        }
        try {
            [$record, $definition] = $this->definitions->resolve($row->module_key, $row->operation);
            if ($row->manifest_hash !== $record->manifestHash) {
                throw new RuntimeException('Queued work source changed.');
            }
            $context = null;
            try {
                if (! $row->cancel_requested) {
                    $this->eventAdmission($row);
                    $context = $this->guard->enter($row->module_key, new ApiOperation($row->operation, $definition->permission));
                    $this->eventAdmission($row, false);
                    $this->guard->assertCurrent($context);
                }
            } catch (Throwable) {
                // Availability is input to the module policy, never an inferred business retry policy.
            }
            $decision = $this->definitions->policy($record, $definition)->pending($this->status($id), $context !== null);
            if ($decision->action !== 'run') {
                return $this->pendingDecision($id, $definition, $decision);
            }
            if ($row->cancel_requested || $context === null) {
                return $this->pendingDecision($id, $definition, new WorkDecision('refuse', 'admission_refused'));
            }
            $token = (string) Str::uuid7();
            $claimed = $this->connection->transaction(function () use ($id, $context, $definition, $token): bool {
                $this->lock();
                $this->guard->assertCurrent($context);

                return $this->query($id)->where('status', 'pending')->where('cancel_requested', false)->where('attempts', '<', $definition->maxAttempts)->update([
                    'status' => 'running', 'lease_token' => $token, 'lease_until' => now()->addSeconds($definition->leaseSeconds),
                    'attempts' => $this->connection->raw('attempts + 1'), 'generation' => $context->generation, 'progress' => 'started', 'updated_at' => now(),
                ]) === 1;
            });
            if (! $claimed) {
                $this->query($id)->where('status', 'pending')->where('attempts', '>=', $definition->maxAttempts)->update(['status' => 'refused', 'progress' => 'attempts_exhausted', 'updated_at' => now()]);

                return null;
            }
            $execution = new OperationContext($row->module_key, $row->operation, $id, $row->correlation_id, $context->generation, $row->origin_module);
            try {
                $this->scope->run($execution, function () use ($record, $definition, $context, $id, $token, $row): void {
                    $handler = $this->definitions->handler($record, $definition);
                    $this->guard->assertCurrent($context);
                    $this->expire($id);
                    if (! $this->query($id)->where('status', 'running')->where('lease_token', $token)->where('lease_until', '>', now())->where('cancel_requested', false)->exists()) {
                        $this->finish($id, $token, 'refused', 'admission_changed');
                        throw new WorkStopped('Admission changed before handler invocation.');
                    }
                    $payload = $this->payload($row);
                    $handler->handle(new WorkContext($id, $token, $this), $payload);
                });
                $this->finish($id, $token, 'succeeded', 'completed');

                return null;
            } catch (WorkStopped) {
                $this->finish($id, $token, 'recovery_required', 'checkpoint_recheck_required');
                $current = $this->row($id);

                return $current->status === 'pending' ? max(1, (int) ceil(now()->diffInSeconds(Carbon::parse($current->available_at)))) : null;
            } catch (Throwable) {
                $this->finish($id, $token, 'failed', 'handler_failed');

                // No blind replay of partially executed effects. Module reconciliation owns recovery.
                return null;
            }
        } catch (Throwable) {
            $this->query($id)->where('status', 'pending')->update(['status' => 'refused', 'progress' => 'policy_unavailable', 'updated_at' => now()]);

            return null;
        }
    }

    /** Stable subscriber key, retained across duplicate envelopes and explicit reconciliation retries. */
    public function deliveryKey(string $id): ?string
    {
        return $this->row($id)->delivery_key;
    }

    /** Only called at module-declared safe boundaries, outside database transactions. */
    public function checkpoint(string $id, string $token, string $progress): WorkStatus
    {
        if ($this->connection->transactionLevel() !== 0 || preg_match('/\A[a-z][a-z0-9_]{0,63}\z/D', $progress) !== 1) {
            throw new RuntimeException('Checkpoint requires a safe code outside an atomic database unit.');
        }
        $this->expire($id);
        $row = $this->row($id);
        if ($row->status !== 'running' || $row->lease_token !== $token) {
            throw new WorkStopped('Work lease is no longer owned.');
        }
        [$record, $definition] = $this->definitions->resolve($row->module_key, $row->operation);
        $decision = $this->definitions->policy($record, $definition)->checkpoint($this->status($id));
        if ($decision->action === 'run') {
            $changed = $this->query($id)->where('status', 'running')->where('lease_token', $token)->where('lease_until', '>', now())
                ->where('cancel_requested', $row->cancel_requested)->update(['progress' => $progress, 'lease_until' => now()->addSeconds($definition->leaseSeconds), 'updated_at' => now()]);
            if ($changed !== 1) {
                throw new WorkStopped('Work state changed during checkpoint.');
            }

            return $this->status($id);
        }
        $delay = $this->delay($definition, $decision);
        $status = $decision->action === 'retry' && $row->attempts < $definition->maxAttempts ? 'pending' : ($decision->action === 'cancel' ? 'cancelled' : 'refused');
        $this->query($id)->where('status', 'running')->where('lease_token', $token)->where('lease_until', '>', now())->update([
            'status' => $status, 'lease_token' => null, 'lease_until' => null, 'available_at' => now()->addSeconds($delay), 'progress' => $decision->code, 'updated_at' => now(),
        ]);
        throw new WorkStopped('Module stopped at a safe checkpoint.');
    }

    /** Cooperative request only. Never confirms cancellation or kills a process. */
    public function requestCancellation(string $id): WorkStatus
    {
        $this->query($id)->whereIn('status', self::ACTIVE)->update(['cancel_requested' => true, 'updated_at' => now()]);

        return $this->status($id);
    }

    public function status(string $id): WorkStatus
    {
        $this->expire($id);
        $row = $this->row($id);
        $capabilities = null;
        try {
            [, $capabilities] = $this->definitions->resolve($row->module_key, $row->operation);
        } catch (Throwable) {
            // Missing source does not erase retained work evidence or invent cancellation capability.
        }

        return new WorkStatus($id, $row->module_key, $row->operation, $row->status, $row->generation, $this->registry->snapshot()->generation, $row->attempts, $row->cancel_requested, $row->lease_until, $row->progress, $row->correlation_id, $capabilities?->cancellable, $capabilities?->limitations);
    }

    /** Bounded management view; terminal evidence remains addressable by work ID.
     * @return list<WorkStatus>
     */
    public function active(string $module, int $limit = 100): array
    {
        if ($limit < 1 || $limit > 1000) {
            throw new RuntimeException('Invalid active-work limit.');
        }
        $ids = $this->connection->table('shared.module_work as w')->join('shared.modules as m', 'm.id', '=', 'w.module_id')->where('m.module_id', $module)->whereIn('w.status', self::ACTIVE)->orderBy('w.id')->limit($limit)->pluck('w.public_id');
        $statuses = [];
        foreach ($ids as $id) {
            if (! is_string($id)) {
                throw new RuntimeException('Invalid stored work identity.');
            }
            $statuses[] = $this->status($id);
        }

        return $statuses;
    }

    /** Explicit recovery after module-owned reconciliation; no automatic crash replay. */
    public function recover(string $id): WorkStatus
    {
        $this->expire($id);
        $row = $this->row($id);
        [$record, $definition] = $this->definitions->resolve($row->module_key, $row->operation);
        $decision = $this->definitions->policy($record, $definition)->pending($this->status($id), false);
        if (! in_array($row->status, ['failed', 'recovery_required'], true) || $record->manifestHash !== $row->manifest_hash) {
            throw new RuntimeException('Work is not eligible for module reconciliation.');
        }
        if (in_array($decision->action, ['cancel', 'refuse'], true)) {
            $changed = $this->query($id)->where('status', $row->status)->update(['status' => $decision->action === 'cancel' ? 'cancelled' : 'refused', 'progress' => $decision->code, 'updated_at' => now()]);
            if ($changed !== 1) {
                throw new RuntimeException('Work recovery state changed.');
            }

            return $this->status($id);
        }
        if ($decision->action !== 'retry' || $row->attempts >= $definition->maxAttempts) {
            throw new RuntimeException('Module has not authorized bounded recovery.');
        }
        $delay = $this->delay($definition, $decision);
        $this->connection->transaction(function () use ($id, $row, $definition, $delay): void {
            $changed = $this->query($id)->where('status', $row->status)->update(['status' => 'pending', 'available_at' => now()->addSeconds($delay), 'progress' => 'recovery_queued', 'updated_at' => now()]);
            if ($changed !== 1) {
                throw new RuntimeException('Work recovery state changed.');
            }
            $this->queues->connection('database')->later($delay, new ModuleJob($id, $definition->leaseSeconds - 5));
        });

        return $this->status($id);
    }

    private function pendingDecision(string $id, JobDefinition $definition, WorkDecision $decision): ?int
    {
        return $this->connection->transaction(function () use ($id, $definition, $decision): ?int {
            /** @var object{status: string, attempts: int}|null $row */
            $row = $this->query($id)->lockForUpdate()->first();
            if ($row === null || $row->status !== 'pending') {
                return null;
            }
            $delay = $this->delay($definition, $decision);
            $retry = $decision->action === 'retry' && $row->attempts + 1 < $definition->maxAttempts;
            $this->query($id)->update(['status' => $retry ? 'pending' : ($decision->action === 'cancel' ? 'cancelled' : 'refused'), 'attempts' => min(100, $row->attempts + 1), 'available_at' => now()->addSeconds($delay), 'progress' => $decision->code, 'updated_at' => now()]);

            return $retry ? $delay : null;
        });
    }

    private function delay(JobDefinition $definition, WorkDecision $decision): int
    {
        $delay = $decision->retrySeconds ?? $definition->retrySeconds;
        if ($delay < $definition->retrySeconds) {
            throw new RuntimeException('Module retry is below its declared lower bound.');
        }

        return $delay;
    }

    private function finish(string $id, string $token, string $status, string $code): void
    {
        $this->expire($id);
        $this->query($id)->where('status', 'running')->where('lease_token', $token)->update(['status' => $status, 'progress' => $code, 'lease_token' => null, 'lease_until' => null, 'updated_at' => now()]);
    }

    private function expire(string $id): void
    {
        $this->query($id)->where('status', 'running')->where('lease_until', '<=', now())->update(['status' => 'recovery_required', 'progress' => 'lease_expired', 'lease_token' => null, 'lease_until' => null, 'updated_at' => now()]);
    }

    private function lock(): void
    {
        $this->connection->table('shared.module_registry_state')->where('id', 1)->lockForUpdate()->first();
    }

    private function query(string $id): Builder
    {
        return $this->connection->table('shared.module_work')->where('public_id', $id);
    }

    /** @return WorkRow */
    private function row(string $id): object
    {
        /** @var WorkRow|null $row */
        $row = $this->connection->table('shared.module_work as w')->join('shared.modules as m', 'm.id', '=', 'w.module_id')->where('w.public_id', $id)->select(['w.*', 'm.module_id as module_key'])->first();

        return $row ?? throw new RuntimeException('Unknown module work.');
    }
}
