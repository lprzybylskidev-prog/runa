<?php

namespace Runa\ModuleFramework\Transactions;

use Closure;
use Illuminate\Database\Connection;
use Runa\Contracts\Modules\ApiDefinition;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\ModuleRecord;
use RuntimeException;

/** One logical root only. Local evidence is not a client token or a health cache. */
final class HealthAdmissions
{
    /** @var array<string, array{source:string, policy:string, expiry:int}>|null */
    private ?array $evidence = null;

    private bool $sealed = false;

    /** @var array<string,array{source:string,policy:string,expiry:int}>|null */
    private ?array $preparingRead = null;

    private ?ReadHealthScope $readScope = null;

    public function __construct(private readonly Connection $connection)
    {
        $admissions = \WeakReference::create($this);
        $connection->beforeExecuting(static function () use ($admissions): void {
            $admissions->get()?->readScope?->observe();
        });
    }

    /** @param array{string,string,string,string} $identity
     * @param Closure():void $prepare */
    public function prepareRead(array $identity, Closure $prepare, ?string $definitionFingerprint = null): PreparedReadCall
    {
        if ($this->evidence !== null || $this->readScope !== null || $this->preparingRead !== null || $this->connection->transactionLevel() !== 0) {
            throw new RuntimeException('Nested health preparation refused.');
        }
        $pdo = $this->connection->getPdo();
        $this->preparingRead = [];
        try {
            $prepare();

            if ($this->connection->getPdo() !== $pdo || $this->connection->transactionLevel() !== 0) {
                throw new RuntimeException('Health preparation connection changed.');
            }

            return new PreparedReadCall($identity, $this->preparingRead, $pdo, $definitionFingerprint);
        } finally {
            $this->preparingRead = null;
        }
    }

    /** @template T
     * @param list<PreparedReadCall> $calls
     * @param Closure():T $work
     * @return T */
    public function read(array $calls, Connection $connection, Closure $work): mixed
    {
        if ($this->evidence !== null || $this->readScope !== null || $this->preparingRead !== null || $connection->transactionLevel() === 0) {
            throw new RuntimeException('Read health admission requires its own locked handler scope.');
        }
        $level = $connection->transactionLevel();
        $scope = new ReadHealthScope($calls, $connection, $connection->getPdo(), $level);
        $this->readScope = $scope;
        try {
            $scope->assertCurrent();
            $result = $work();
            $scope->assertCurrent();
            if ($connection->transactionLevel() !== $level) {
                throw new OperationRejected('admission_changed', 'runa.admin', 'section.read', 409);
            }

            return $result;
        } finally {
            try {
                if ($connection->transactionLevel() > $level) {
                    $connection->rollBack($level);
                }
            } finally {
                $this->readScope = null;
            }
        }
    }

    /** @template T
     * @param array{string,string,string,string} $identity
     * @param Closure():T $work
     * @return T */
    public function invoke(array $identity, Closure $work): mixed
    {
        $scope = $this->readScope;
        if ($scope === null) {
            return $work();
        }
        $scope->assertCurrent();
        $call = $scope->calls[$scope->next] ?? null;
        $claimed = null;
        if ($call !== null && $call->identity === $identity) {
            $claimed = $call;
            $scope->next++;
        }
        $scope->frames[] = $claimed;
        try {
            $result = $work();
            $scope->assertCurrent();

            return $result;
        } finally {
            array_pop($scope->frames);
        }
    }

    /** Internal gateway fence before constructing a prepared call's implementation. */
    public function assertReadDefinition(ApiDefinition $definition): void
    {
        $scope = $this->readScope;
        $call = $scope === null || $scope->frames === [] ? null : $scope->frames[array_key_last($scope->frames)];
        if ($call?->definitionFingerprint !== null && $call->definitionFingerprint !== hash('sha256', serialize($definition))) {
            $scope->invalid = true;
            throw new OperationRejected('admin_read_plan_changed', 'runa.admin', 'section.read', 409);
        }
    }

    /** @template T
     * @param  Closure(): void  $prepare
     * @param  Closure(): T  $work
     * @return T
     */
    public function run(Closure $prepare, Closure $work): mixed
    {
        if ($this->evidence !== null || $this->readScope !== null || $this->preparingRead !== null) {
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

    /** @template T
     * @param Closure():void $prepare
     * @param Closure():T $work
     * @return T */
    public function within(Closure $prepare, Closure $work): mixed
    {
        if ($this->preparingRead !== null || ($this->evidence !== null && ! $this->sealed)) {
            throw new RuntimeException('Health admission is still preparing.');
        }
        if ($this->readScope !== null || $this->evidence !== null) {
            $this->readScope?->assertCurrent();
            $this->assertCurrent();
            $result = $work();
            $this->readScope?->assertCurrent();
            $this->assertCurrent();

            return $result;
        }

        if ($this->connection->transactionLevel() !== 0) {
            throw new RuntimeException('Standalone health admission requires an independent transaction boundary.');
        }

        return $this->run($prepare, $work);
    }

    public function reuse(ModuleRecord $record, ?string $operation, string $fingerprint): bool
    {
        if ($this->readScope !== null) {
            $scope = $this->readScope;
            $scope->assertCurrent();
            $frame = $scope->frames === [] ? null : $scope->frames[array_key_last($scope->frames)];
            $entry = ($frame?->evidence ?? [])[$record->id.':'.($operation ?? '*')] ?? null;
            if ($entry === null || $entry['source'] !== $record->manifestHash || $entry['policy'] !== $fingerprint || $entry['expiry'] <= now()->getTimestamp()) {
                $scope->invalid = true;
                throw new RuntimeException('Read call health admission missing or changed.');
            }

            return true;
        }
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
        if ($this->preparingRead === null && ($this->evidence === null || $this->sealed)) {
            return;
        }
        $expiry = PHP_INT_MAX;
        foreach ($checks as $id) {
            $expiry = min($expiry, $observations[$id]['expires_at']);
        }
        $entry = ['source' => $record->manifestHash, 'policy' => $fingerprint, 'expiry' => $expiry];
        if ($this->preparingRead !== null) {
            $this->preparingRead[$record->id.':'.($operation ?? '*')] = $entry;

            return;
        }
        $this->evidence[$record->id.':'.($operation ?? '*')] = ['source' => $record->manifestHash, 'policy' => $fingerprint, 'expiry' => $expiry];
    }
}
