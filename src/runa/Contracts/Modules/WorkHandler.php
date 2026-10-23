<?php

namespace Runa\Contracts\Modules;

use Runa\Support\Modules\Work\WorkContext;

interface WorkHandler
{
    /** Payload is JSON data only. Idempotency/reconciliation uses context.workId.
     * @param  array<array-key, mixed>  $payload
     */
    public function handle(WorkContext $context, array $payload): void;
}
