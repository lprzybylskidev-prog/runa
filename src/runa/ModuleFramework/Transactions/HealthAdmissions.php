<?php

namespace Runa\ModuleFramework\Transactions;

use Closure;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\ModuleRecord;
use RuntimeException;

/** One logical root only. Local evidence is not a client token or a health cache. */
final class HealthAdmissions
{
    /** @var array<string, array{source:string, policy:string, expiry:int}>|null */
    private ?array $evidence = null;

    private bool $sealed = false;

    /** @template T
     * @param  Closure(): void  $prepare
     * @param  Closure(): T  $work
     * @return T
     */
    public function run(Closure $prepare, Closure $work): mixed
    {
        if ($this->evidence !== null) {
            throw new RuntimeException('Nested mutation admission is not supported.');
        }
        $this->evidence = [];
        try {
            $prepare();
            $this->sealed = true;

            return $work();
        } finally {
            $this->evidence = null;
            $this->sealed = false;
        }
    }

    public function reuse(ModuleRecord $record, ?string $operation, string $fingerprint): bool
    {
        if ($this->evidence === null) {
            return false;
        }
        $entry = $this->evidence[$record->id.':'.($operation ?? '*')] ?? null;
        if ($entry !== null) {
            if ($entry['source'] !== $record->manifestHash || $entry['policy'] !== $fingerprint || $entry['expiry'] <= now()->getTimestamp()) {
                throw new RuntimeException('Mutation health admission changed or expired.');
            }

            return true;
        }
        if ($this->sealed) {
            throw new RuntimeException('Operation requires health admission before the transaction.');
        }

        return false;
    }

    public function assertCurrent(): void
    {
        foreach ($this->evidence ?? [] as $entry) {
            if ($entry['expiry'] <= now()->getTimestamp()) {
                throw new OperationRejected('admission_expired', 'runa', 'transaction', 409);
            }
        }
    }

    /** @param array<string, array{fingerprint:string, passed:bool, checked_at:int, expires_at:int, code:string}> $observations
     * @param  list<string>  $checks
     */
    public function remember(ModuleRecord $record, ?string $operation, string $fingerprint, array $observations, array $checks): void
    {
        if ($this->evidence === null || $this->sealed) {
            return;
        }
        $expiry = PHP_INT_MAX;
        foreach ($checks as $id) {
            $expiry = min($expiry, $observations[$id]['expires_at']);
        }
        $this->evidence[$record->id.':'.($operation ?? '*')] = ['source' => $record->manifestHash, 'policy' => $fingerprint, 'expiry' => $expiry];
    }
}
