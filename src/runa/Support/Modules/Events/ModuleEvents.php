<?php

namespace Runa\Support\Modules\Events;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\EventListener;
use Runa\Contracts\Modules\OperationContext;
use Runa\Contracts\Modules\PublicEvent;
use Runa\ModuleFramework\Api\ModuleDeclarations;
use Runa\ModuleFramework\Api\OperationGuard;
use Runa\ModuleFramework\Api\OperationScope;
use Runa\ModuleFramework\Events\EventDefinitions;
use Runa\ModuleFramework\Events\EventSubscriptions;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\Support\Modules\Work\ModuleWork;
use RuntimeException;
use Throwable;

/** Independent facts only. Critical coordinated operations use the fail-closed ModuleApis boundary. */
final readonly class ModuleEvents
{
    public function __construct(private Connection $connection, private ModuleRegistry $registry, private EventDefinitions $definitions, private EventSubscriptions $subscriptions, private ModuleDeclarations $declarations, private OperationGuard $guard, private OperationScope $scope, private ModuleWork $work, private LoggerInterface $logger) {}

    /** @param array<array-key, mixed> $payload */
    public function dispatch(string $module, string $key, int $version, array $payload, ?string $eventId = null): PublicEvent
    {
        return $this->prepare($module, $key, $version, $payload, $eventId)();
    }

    /** Admit before a business transaction; invoke in it to register delivery after its outer commit.
     * @param  array<array-key, mixed>  $payload
     * @return Closure(): PublicEvent
     */
    public function prepare(string $module, string $key, int $version, array $payload, ?string $eventId = null): Closure
    {
        $record = $this->registry->snapshot()->modules[$module] ?? throw new RuntimeException('Unknown event publisher.');
        [$events] = $this->definitions->for($record);
        if (! isset($events[$key.':'.$version])) {
            throw new RuntimeException('Unknown public fact.');
        }
        $context = $this->guard->enter($module, new ApiOperation($key));
        $event = new PublicEvent($eventId ?? (string) Str::uuid7(), $module, $key, $version, $context->correlationId, $payload);

        return function () use ($event, $context): PublicEvent {
            $this->guard->assertCurrent($context);
            // Laravel discards callbacks on rollback/savepoint rollback. Subscriber failures never escape this callback.
            if ($this->connection->transactionLevel() > 0) {
                $this->connection->afterCommit(fn () => $this->deliver($event));
            } else {
                $this->deliver($event);
            }

            return $event;
        };
    }

    private function deliver(PublicEvent $event): void
    {
        try {
            $records = $this->registry->snapshot()->modules;
        } catch (Throwable) {
            $this->failure($event, null, 'registry_unavailable');

            return;
        }
        foreach ($records as $record) {
            if (! $record->registered || ! $record->sourcePresent) {
                continue;
            }
            try {
                [, $listeners] = $this->definitions->for($record);
            } catch (Throwable) {
                $this->failure($event, $record->id, 'declaration_unavailable');

                continue;
            }
            foreach ($listeners as $listener) {
                if ($listener->publisher !== $event->module || $listener->event !== $event->event || $listener->version !== $event->version) {
                    continue;
                }
                try {
                    $current = $this->subscriptions->resolve($record->id, $listener->key, $event);
                    if ($current->mode === 'async') {
                        $this->work->dispatchEvent($record->id, $current->key, $event);

                        continue;
                    }
                    $context = $this->guard->enter($record->id, new ApiOperation($current->key));
                    if ($this->subscriptions->resolve($record->id, $current->key, $event, false) != $current) {
                        throw new RuntimeException('Listener binding changed.');
                    }
                    $this->guard->assertCurrent($context);
                    $execution = new OperationContext($record->id, $current->key, $event->id, $event->correlationId, $context->generation, $event->module);
                    $this->scope->run($execution, function () use ($record, $current, $event, $context): void {
                        $handler = $this->declarations->make($record, $current->target, EventListener::class);
                        if (! $handler instanceof EventListener) {
                            throw new RuntimeException('Invalid public listener.');
                        }
                        $this->guard->assertCurrent($context);
                        $handler->handle($event, EventSubscriptions::key($record->id, $current->key, $event));
                    }, ['event_id' => $event->id]);
                } catch (Throwable) {
                    $this->failure($event, $record->id, 'listener_refused_or_failed', $listener->key);
                }
            }
        }
    }

    private function failure(PublicEvent $event, ?string $subscriber, string $code, ?string $listener = null): void
    {
        try {
            $this->logger->warning('Module event delivery unavailable.', ['event_id' => $event->id, 'origin_module' => $event->module, 'subscriber_module' => $subscriber, 'correlation_id' => $event->correlationId, 'code' => $code, 'listener_key' => $listener]);
        } catch (Throwable) { /* A broken log sink cannot turn a committed fact into a failed origin. */
        }
    }
}
