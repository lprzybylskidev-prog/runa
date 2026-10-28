<?php

namespace Runa\Modules\Audit\Contracts;

use Runa\Contracts\Modules\ApiOperation;
use Runa\Modules\Audit\Support\SafePayload;
use Runa\Support\Modules\ModuleIdentity;

/** Source-owned allowlist; never derive this policy from request input. */
final readonly class AuditModelPolicy
{
    /** @var list<string> */
    public array $fields;

    /** @var list<string> */
    public array $events;

    /** @var list<string> */
    public array $sensitiveFields;

    /** @param array<array-key, mixed> $fields
     * @param  array<array-key, mixed>  $events
     * @param  array<array-key, mixed>  $sensitiveFields
     */
    public function __construct(
        public string $module, public string $type, array $fields,
        array $events = ['created', 'updated', 'deleted', 'restored', 'force_deleted'],
        public bool $snapshot = false, array $sensitiveFields = [],
    ) {
        $fields = self::strings($fields);
        $events = self::strings($events);
        $sensitiveFields = self::strings($sensitiveFields);
        new ModuleIdentity($module);
        new ApiOperation($type);
        if (strlen($type) > 64 || count($fields) < 1 || count($fields) > 128
            || count(array_unique($events)) !== count($events)
            || array_diff($events, ['created', 'updated', 'deleted', 'restored', 'force_deleted']) !== []) {
            throw new \InvalidArgumentException('Invalid Auditable model policy.');
        }
        foreach ($fields as $field) {
            if (preg_match('/\A[a-z][a-z0-9_]{0,62}\z/D', $field) !== 1 || $field === 'id') {
                throw new \InvalidArgumentException('Audit requires explicit approved attribute names.');
            }
        }
        SafePayload::clean([], $sensitiveFields);
        $this->fields = $fields;
        $this->events = $events;
        $this->sensitiveFields = $sensitiveFields;
    }

    /** @param array<array-key, mixed> $values
     * @return list<string>
     */
    private static function strings(array $values): array
    {
        if (! array_is_list($values)) {
            throw new \InvalidArgumentException('Invalid Auditable model policy.');
        }
        $result = [];
        foreach ($values as $value) {
            if (! is_string($value)) {
                throw new \InvalidArgumentException('Invalid Auditable model policy.');
            }
            $result[] = $value;
        }

        return $result;
    }
}
