<?php

namespace Runa\ModuleFramework\Transactions;

use Illuminate\Database\Connection;
use PDO;
use Runa\Support\Modules\Api\OperationRejected;

/** A single handler owns these consumable occurrences; nested API frames stay distinct. */
final class ReadHealthScope
{
    public bool $invalid = false;

    public int $next = 0;

    /** @var array<int,true> */
    private array $observedLevels = [];

    /** @var list<PreparedReadCall|null> */
    public array $frames = [];

    /** @param list<PreparedReadCall> $calls */
    public function __construct(public readonly array $calls, private readonly Connection $connection, private readonly PDO $pdo, private readonly int $level)
    {
        foreach ($calls as $call) {
            if ($call->claimed) {
                throw new OperationRejected('admission_consumed', 'runa.admin', 'section.read', 409);
            }
            $call->claimed = true;
        }
    }

    /** Connection callbacks also run when database events are suppressed. */
    public function observe(): void
    {
        $level = $this->connection->transactionLevel();
        if ($level >= $this->level && ! isset($this->observedLevels[$level])) {
            $this->observedLevels[$level] = true;
            $this->connection->afterRollBack(function (): void {
                $this->invalid = true;
            });
        }
    }

    public function assertCurrent(): void
    {
        $this->observe();
        if ($this->invalid || $this->connection->getPdo() !== $this->pdo || $this->connection->transactionLevel() < $this->level) {
            throw new OperationRejected('admission_changed', 'runa.admin', 'section.read', 409);
        }
        foreach ($this->calls as $call) {
            if ($call->pdo !== $this->pdo) {
                throw new OperationRejected('admission_changed', 'runa.admin', 'section.read', 409);
            }
        }
        foreach ($this->calls as $call) {
            foreach ($call->evidence as $entry) {
                if ($entry['expiry'] <= now()->getTimestamp()) {
                    throw new OperationRejected('admission_expired', 'runa.admin', 'section.read', 409);
                }
            }
        }
    }
}
