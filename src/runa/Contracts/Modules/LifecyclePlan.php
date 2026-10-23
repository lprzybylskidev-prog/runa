<?php

namespace Runa\Contracts\Modules;

use InvalidArgumentException;

/** Safe public plan; the revision covers all module-owned settings and decision inputs. */
final readonly class LifecyclePlan
{
    public function __construct(public string $revision, public bool $blocked = false, public string $code = 'ready')
    {
        if (preg_match('/\A[a-zA-Z0-9_.:-]{1,128}\z/D', $revision) !== 1 || preg_match('/\A[a-z][a-z0-9_]{0,63}\z/D', $code) !== 1) {
            throw new InvalidArgumentException('Invalid lifecycle plan metadata.');
        }
    }
}
