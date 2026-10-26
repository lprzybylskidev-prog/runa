<?php

namespace Runa\Modules\Audit\Contracts;

use Closure;

/** Trusted local helper, deliberately not a ModuleApis operation. */
final class AuditSuppression
{
    private int $depth = 0;

    public function active(): bool
    {
        return $this->depth > 0;
    }

    /** @template T
     * @param  Closure(): T  $work
     * @return T
     */
    public function run(Closure $work): mixed
    {
        $this->depth++;
        try {
            return $work();
        } finally {
            $this->depth--;
        }
    }
}
