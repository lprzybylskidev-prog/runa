<?php

namespace Runa\Modules\Audit\Storage;

use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Runa\Modules\Audit\Contracts\AuditActorResolver;
use Runa\Modules\Audit\Contracts\AuditEvent;
use Runa\Modules\Audit\Contracts\AuditReference;
use Runa\Modules\Audit\Contracts\AuditUnavailable;
use Runa\Modules\Audit\Contracts\AuditWriter;
use Runa\Modules\Audit\Support\ExecutionContext;
use Runa\Modules\Audit\Support\TrustedActor;

/** Deliberately no Eloquent mutable model, update, delete, retention or queue path. */
final readonly class AppendEvents implements AuditWriter
{
    public function __construct(private DatabaseManager $database, private ExecutionContext $execution, private TrustedActor $defaultActor) {}

    public function record(Connection $connection, AuditEvent $event): AuditReference
    {
        if ($connection !== $this->database->connection() || $connection->getDriverName() !== 'pgsql' || $connection->transactionLevel() < 1) {
            throw new AuditUnavailable('Audit requires the active default PostgreSQL transaction.');
        }
        try {
            // Savepoint makes a partial event/entity insertion impossible even if the root catches failure.
            return $connection->transaction(function () use ($connection, $event): AuditReference {
                $resolver = app()->bound(AuditActorResolver::class) ? app(AuditActorResolver::class) : $this->defaultActor;
                $actor = $resolver->resolve();
                $execution = $this->execution->capture();
                $reference = new AuditReference((string) Str::uuid7(), now('UTC')->format('Y-m-d\TH:i:s.uP'));
                $id = $connection->table('runa_audit.events')->insertGetId([
                    'public_id' => $reference->publicId, 'occurred_at' => $reference->occurredAt,
                    'module' => $event->module, 'action' => $event->action, 'outcome' => $event->outcome,
                    'actor_kind' => $actor->kind, 'actor_public_id' => $actor->publicId, 'actor_label' => $actor->label,
                    'request_id' => $execution['request_id'] ?? null, 'correlation_id' => $execution['correlation_id'] ?? null,
                    'operation_id' => $execution['operation_id'] ?? null,
                    'changes' => json_encode($event->changes, JSON_THROW_ON_ERROR),
                    'context' => json_encode($event->context, JSON_THROW_ON_ERROR),
                    'execution' => json_encode($execution, JSON_THROW_ON_ERROR),
                ]);
                $entities = [];
                foreach ($event->entities as $entity) {
                    $entities[$entity->module.':'.$entity->type.':'.$entity->publicId] = [
                        'event_id' => $id, 'module' => $entity->module, 'entity_type' => $entity->type, 'entity_public_id' => $entity->publicId,
                    ];
                }
                if ($entities !== []) {
                    $connection->table('runa_audit.entities')->insert(array_values($entities));
                }

                return $reference;
            });
        } catch (\Throwable $error) {
            throw new AuditUnavailable('Audit persistence unavailable; the operation must roll back.', previous: $error);
        }
    }
}
