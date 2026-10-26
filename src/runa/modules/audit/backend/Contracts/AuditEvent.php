<?php

namespace Runa\Modules\Audit\Contracts;

use Runa\Contracts\Modules\ApiOperation;
use Runa\Modules\Audit\Support\SafePayload;
use Runa\Support\Modules\ModuleIdentity;

/** Trusted callers declare approved business values; never pass requests/models wholesale. */
final readonly class AuditEvent
{
    public array $changes;

    public array $context;

    /** @param list<AuditEntity> $entities
     * @param  array<string, mixed>  $changes
     * @param  list<string>  $sensitiveFields  Application-specific credential aliases.
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public string $module, public string $action, public string $outcome = 'success',
        public array $entities = [], array $changes = [], array $context = [], array $sensitiveFields = [],
    ) {
        new ModuleIdentity($module);
        new ApiOperation($action);
        if (! in_array($outcome, ['success', 'attempt', 'refused', 'failure'], true)
            || ! array_is_list($entities) || count($entities) > 64) {
            throw new \InvalidArgumentException('Invalid Audit event.');
        }
        foreach ($entities as $entity) {
            if (! $entity instanceof AuditEntity) {
                throw new \InvalidArgumentException('Audit entities require immutable references.');
            }
        }
        $this->changes = SafePayload::clean($changes, $sensitiveFields);
        $this->context = SafePayload::clean($context, $sensitiveFields);
    }
}
