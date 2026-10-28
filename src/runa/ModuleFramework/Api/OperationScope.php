<?php

namespace Runa\ModuleFramework\Api;

use Closure;
use Runa\Contracts\Modules\OperationContext;

/** Synchronous operation nesting; always unwind before reusing a worker. */
final class OperationScope
{
    /** @var list<OperationContext> */
    private array $stack = [];

    /** @var list<array{event_id?: string, work_id?: string}> */
    private array $diagnostics = [];

    /** @return array{event_id?: string, work_id?: string} */
    public function diagnostics(): array
    {
        return $this->diagnostics === [] ? [] : $this->diagnostics[array_key_last($this->diagnostics)];
    }

    public function current(): ?OperationContext
    {
        return $this->stack === [] ? null : $this->stack[array_key_last($this->stack)];
    }

    /** @template T
     * @param  Closure(): T  $callback
     * @param  array{event_id?: string, work_id?: string}  $diagnostics
     * @return T
     */
    public function run(OperationContext $context, Closure $callback, array $diagnostics = []): mixed
    {
        $this->stack[] = $context;
        $this->diagnostics[] = array_replace($this->diagnostics(), $diagnostics);
        try {
            return $callback();
        } finally {
            array_pop($this->stack);
            array_pop($this->diagnostics);
        }
    }
}
