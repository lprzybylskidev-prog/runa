<?php

namespace Runa\Modules\Audit\Contracts;

use Runa\Contracts\Modules\ApiOperation;
use Runa\Modules\Audit\Support\SafePayload;
use Runa\Support\Modules\ModuleIdentity;

/** Trusted callers declare approved business values; never pass requests/models wholesale. */
final readonly class AuditEvent
{
    /** @var list<AuditEntity> */
    public array $entities;

    /** @var array<array-key, mixed> */
    public array $changes;

    /** @var array<array-key, mixed> */
    public array $context;

    /** @param array<array-key, mixed> $entities
     * @param  array<array-key, mixed>  $changes
     * @param  list<string>  $sensitiveFields  Application-specific credential aliases.
     * @param  array<array-key, mixed>  $context
     */
    public function __construct(
        public string $module, public string $action, public string $outcome = 'success',
        array $entities = [], array $changes = [], array $context = [], array $sensitiveFields = [],
    ) {
        new ModuleIdentity($module);
        new ApiOperation($action);
        if (! in_array($outcome, ['success', 'attempt', 'refused', 'failure'], true)
            || ! array_is_list($entities) || count($entities) > 64) {
            throw new \InvalidArgumentException('Invalid Audit event.');
        }
        $approved = [];
        foreach ($entities as $entity) {
            if (! $entity instanceof AuditEntity) {
                throw new \InvalidArgumentException('Audit entities require immutable references.');
            }
            $approved[] = $entity;
        }
        $this->entities = $approved;
        $this->changes = SafePayload::clean($changes, $sensitiveFields);
        $this->context = SafePayload::clean($context, $sensitiveFields);
    }
}
