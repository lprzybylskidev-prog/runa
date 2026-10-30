<?php

namespace Runa\Integration\Core;

use Closure;

/** Request-scoped native runner authorization, unwound before another trusted operation. */
final class AdminOperationalAuthority
{
    /** @var (Closure():void)|null */
    private ?Closure $check = null;

    /** @template T @param Closure():void $check @param Closure():T $work @return T */
    public function run(Closure $check, Closure $work): mixed
    {
        $previous = $this->check;
        $this->check = $check;
        try {
            return $work();
        } finally {
            $this->check = $previous;
        }
    }

    /** Called inside the actual native transaction, including initial reservation. */
    public function assertCurrent(): void
    {
        ($this->check)?->__invoke();
    }
}
