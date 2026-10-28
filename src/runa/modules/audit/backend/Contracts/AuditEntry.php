<?php

namespace Runa\Modules\Audit\Contracts;

/** Summary omits payloads; detail contains the same immutable metadata plus safe payloads. */
final readonly class AuditEntry
{
    /** @param list<AuditEntity> $entities
     * @param  array<array-key, mixed>|null  $changes
     * @param  array<array-key, mixed>|null  $context
     * @param  array<array-key, mixed>|null  $execution
     */
    public function __construct(
        public AuditReference $reference, public string $module, public string $action, public string $outcome,
        public AuditActor $actor, public ?string $correlationId, public array $entities,
        public ?array $changes = null, public ?array $context = null, public ?array $execution = null,
    ) {}
}
