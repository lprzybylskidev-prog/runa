<?php

namespace Runa\Support\Modules\Api;

use Closure;
use Runa\Contracts\Modules\OperationContext;

/** Synchronous operation nesting; always unwind before reusing a worker. */
final class OperationScope
{
    /** @var list<OperationContext> */
    private array $stack = [];

    public function current(): ?OperationContext
    {
        return $this->stack === [] ? null : $this->stack[array_key_last($this->stack)];
    }

    /** @template T
     * @param  Closure(): T  $callback
     * @return T
     */
    public function run(OperationContext $context, Closure $callback): mixed
    {
        $this->stack[] = $context;
        try {
            return $callback();
        } finally {
            array_pop($this->stack);
        }
    }
}
