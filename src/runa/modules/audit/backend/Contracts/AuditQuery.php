<?php

namespace Runa\Modules\Audit\Contracts;

use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\Support\Modules\ModuleIdentity;

final readonly class AuditQuery
{
    public function __construct(
        public ?string $module = null, public int $limit = 50,
        public ?string $action = null, public ?string $actorPublicId = null,
        public ?string $actorKind = null, public ?AuditEntity $entity = null,
        public ?string $outcome = null, public ?string $correlationId = null,
        public ?string $from = null, public ?string $until = null, public ?string $before = null,
    ) {
        if ($module !== null) {
            new ModuleIdentity($module);
        }
        if ($action !== null) {
            new ApiOperation($action);
        }
        foreach ([$actorPublicId, $before] as $id) {
            if ($id !== null) {
                new TrustedPrincipal($id);
            }
        }
        if ($limit < 1 || $limit > 100
            || ($actorKind !== null && ! in_array($actorKind, ['human', 'anonymous', 'system', 'scheduler', 'queue', 'cli', 'machine'], true))
            || ($outcome !== null && ! in_array($outcome, ['success', 'attempt', 'refused', 'failure'], true))
            || ($correlationId !== null && preg_match('/\A[a-zA-Z0-9_-]{1,128}\z/D', $correlationId) !== 1)) {
            throw new \InvalidArgumentException('Invalid bounded Audit query.');
        }
        foreach ([$from, $until] as $time) {
            if ($time !== null) {
                if (strlen($time) > 32 || preg_match('/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|\+00:00)\z/D', $time) !== 1) {
                    throw new \InvalidArgumentException('Audit times require UTC ISO-8601.');
                }
                new \DateTimeImmutable($time);
                if (\DateTimeImmutable::getLastErrors() !== false) {
                    throw new \InvalidArgumentException('Invalid Audit time.');
                }
            }
        }
        if ($from !== null && $until !== null && new \DateTimeImmutable($from) > new \DateTimeImmutable($until)) {
            throw new \InvalidArgumentException('Invalid Audit time range.');
        }
    }
}
