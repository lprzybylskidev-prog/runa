<?php

namespace Runa\Modules\Audit\Contracts;

use Runa\Contracts\Modules\ApiOperation;
use Runa\Modules\Audit\Support\SafePayload;
use Runa\Support\Modules\ModuleIdentity;

/** Source-owned allowlist; never derive this policy from request input. */
final readonly class AuditModelPolicy
{
    /** @param list<string> $fields
     * @param  list<string>  $events
     * @param  list<string>  $sensitiveFields
     */
    public function __construct(
        public string $module, public string $type, public array $fields,
        public array $events = ['created', 'updated', 'deleted', 'restored', 'force_deleted'],
        public bool $snapshot = false, public array $sensitiveFields = [],
    ) {
        new ModuleIdentity($module);
        new ApiOperation($type);
        if (strlen($type) > 64 || ! array_is_list($fields) || count($fields) < 1 || count($fields) > 128
            || ! array_is_list($events) || count(array_unique($events)) !== count($events)
            || array_diff($events, ['created', 'updated', 'deleted', 'restored', 'force_deleted']) !== []) {
            throw new \InvalidArgumentException('Invalid Auditable model policy.');
        }
        foreach ($fields as $field) {
            if (! is_string($field) || preg_match('/\A[a-z][a-z0-9_]{0,62}\z/D', $field) !== 1 || $field === 'id') {
                throw new \InvalidArgumentException('Audit requires explicit approved attribute names.');
            }
        }
        SafePayload::clean([], $sensitiveFields);
    }
}
