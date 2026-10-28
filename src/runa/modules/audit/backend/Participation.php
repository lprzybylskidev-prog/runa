<?php

namespace Runa\Modules\Audit;

use Illuminate\Database\Connection;
use Runa\Contracts\Modules\PreparationContext;
use Runa\Contracts\Modules\PreparationParticipant;
use Runa\Contracts\Modules\SettingsParticipant;
use Runa\Contracts\Modules\SettingsPublication;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\Modules\Audit\Contracts\AuditEvent;
use Runa\Modules\Audit\Storage\AppendEvents;

/** Only the trusted framework adapter uses this class, never customer business code. */
final readonly class Participation implements PreparationParticipant, SettingsParticipant
{
    public function __construct(private AppendEvents $writer, private ModuleRegistry $registry) {}

    public function publishing(Connection $connection, SettingsPublication $publication): void
    {
        $this->writer->record($connection, new AuditEvent($publication->context->module, 'settings.'.$publication->phase,
            changes: $publication->changes, context: ['revision' => $publication->revision]));
    }

    public function preparing(PreparationContext $context): void
    {
        $state = $context->connection->table('shared.module_registry_state')->where('id', 1)->first();
        $record = $this->registry->snapshot()->modules[$context->module] ?? null;
        if ($state === null || $state->update_operation !== $context->operation || $state->update_owner !== $context->owner
            || $state->update_fingerprint !== $context->reservationFingerprint || $record === null
            || ! $record->registered || ! $record->sourcePresent || $record->manifestHash !== $context->manifestHash) {
            throw new \RuntimeException('Audit preparation capability no longer matches the reservation.');
        }
        $this->writer->record($context->connection, new AuditEvent($context->module, 'module.prepare', context: [
            'step' => $context->step, 'fingerprint' => $context->fingerprint, 'operation' => $context->operation,
        ]));
    }
}
