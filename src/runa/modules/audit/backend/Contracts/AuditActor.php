<?php

namespace Runa\Modules\Audit\Contracts;

use Runa\Contracts\Modules\TrustedPrincipal;

final readonly class AuditActor
{
    public function __construct(public string $kind, public ?string $publicId = null, public ?string $label = null)
    {
        if (! in_array($kind, ['human', 'anonymous', 'system', 'scheduler', 'queue', 'cli', 'machine'], true)
            || ($publicId !== null && ! in_array($kind, ['human', 'machine'], true))
            || (in_array($kind, ['human', 'machine'], true) && $publicId === null)
            || ($label !== null && (strlen($label) > 160 || preg_match('/[\x00-\x1f\x7f]/', $label)))) {
            throw new \InvalidArgumentException('Invalid trusted Audit actor.');
        }
        if ($publicId !== null) {
            new TrustedPrincipal($publicId, $kind);
        }
    }
}
