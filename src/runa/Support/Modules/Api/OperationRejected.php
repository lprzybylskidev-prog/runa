<?php

namespace Runa\Support\Modules\Api;

use Illuminate\Http\JsonResponse;
use Runa\Contracts\Modules\GraphBlocker;
use RuntimeException;

final class OperationRejected extends RuntimeException
{
    /** @param list<GraphBlocker> $reasons */
    public function __construct(public readonly string $reason, public readonly string $moduleId, public readonly string $operation, public readonly int $status = 503, public readonly array $reasons = [], public readonly ?int $retryAfter = null)
    {
        parent::__construct('Module operation rejected.');
    }

    public function render(): JsonResponse
    {
        return new JsonResponse(['error' => ['code' => $this->reason, 'module' => $this->moduleId, 'operation' => $this->operation]], $this->status, ['Cache-Control' => 'no-store', ...($this->retryAfter === null ? [] : ['Retry-After' => (string) $this->retryAfter])]);
    }

    public function report(): bool
    {
        return true;
    }
}
