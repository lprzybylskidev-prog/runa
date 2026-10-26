<?php

namespace Runa\Modules\Audit\Actions;

use Illuminate\Database\DatabaseManager;
use Runa\Modules\Audit\Contracts\AuditEvent;
use Runa\Modules\Audit\Contracts\AuditRecords;
use Runa\Modules\Audit\Contracts\AuditReference;
use Runa\Modules\Audit\Contracts\AuditUnavailable;
use Runa\Modules\Audit\Storage\AppendEvents;

final readonly class RecordEvent implements AuditRecords
{
    public function __construct(private DatabaseManager $database, private AppendEvents $writer, private RecordAttempt $attempts) {}

    public function record(AuditEvent $event): AuditReference
    {
        if ($event->outcome !== 'success') {
            throw new AuditUnavailable('Non-success events require the independent attempt entrypoint after root rollback.');
        }

        return $this->writer->record($this->database->connection(), $event);
    }

    public function attempt(AuditEvent $event): AuditReference
    {
        return $this->attempts->record($event);
    }
}
