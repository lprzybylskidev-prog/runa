<?php

namespace Runa\Support\Modules\Api;

use Closure;
use Runa\Contracts\Modules\TrustedPrincipal;
use RuntimeException;

/** No mutable global login state; roots unwind even on failed controller construction. */
final class PrincipalScope
{
    private ?TrustedPrincipal $principal = null;

    private bool $resolving = false;

    public function current(): ?TrustedPrincipal
    {
        return $this->principal;
    }

    /** @template T
     * @param  Closure(): T  $work
     * @return T
     */
    public function run(?TrustedPrincipal $principal, Closure $work): mixed
    {
        $previous = $this->principal;
        $this->principal = $principal;
        try {
            return $work();
        } finally {
            $this->principal = $previous;
        }
    }

    /** Identity/permission resolution cannot recursively reenter protected authorization.
     * @template T
     *
     * @param  Closure(): T  $work
     * @return T
     */
    public function resolve(Closure $work): mixed
    {
        if ($this->resolving) {
            throw new RuntimeException('Recursive identity or permission resolution refused.');
        }
        $this->resolving = true;
        try {
            return $work();
        } finally {
            $this->resolving = false;
        }
    }
}
