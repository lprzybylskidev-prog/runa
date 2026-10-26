<?php

namespace Runa\Modules\Audit\Actions;

use Illuminate\Database\DatabaseManager;
use Runa\Modules\Audit\Contracts\AuditEvent;
use Runa\Modules\Audit\Contracts\AuditReference;
use Runa\Modules\Audit\Contracts\AuditUnavailable;
use Runa\Modules\Audit\Storage\AppendEvents;

/** Root calls only after rollback; never starts another connection to escape a transaction. */
final readonly class RecordAttempt
{
    public function __construct(private DatabaseManager $database, private AppendEvents $writer) {}

    public function record(AuditEvent $event): AuditReference
    {
        $connection = $this->database->connection();
        if ($connection->transactionLevel() !== 0 || $event->outcome === 'success') {
            throw new AuditUnavailable('Independent Audit requires root rollback and a non-success outcome.');
        }
        try {
            return $connection->transaction(fn (): AuditReference => $this->writer->record($connection, $event));
        } catch (\Throwable $error) {
            throw new AuditUnavailable('Independent Audit persistence unavailable; no audited refusal is confirmed.', previous: $error);
        }
    }
}
