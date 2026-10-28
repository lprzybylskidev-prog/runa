<?php

namespace Runa\Modules\Permissions;

use Runa\Contracts\Modules\PreparationContext;
use Runa\Contracts\Modules\PreparationParticipant;
use Runa\ModuleFramework\Api\ModuleDeclarations;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\Modules\Audit\Contracts\AuditWriter;
use Runa\Modules\Permissions\Actions\SynchronizeDeclarations;

/** Reserved default-connection capability only; never ordinary runtime ingress. */
final readonly class Participation implements PreparationParticipant
{
    public function __construct(private ModuleRegistry $registry, private ModuleDeclarations $declarations, private SynchronizeDeclarations $sync) {}

    public function preparing(PreparationContext $context): void
    {
        $state = $context->connection->table('shared.module_registry_state')->where('id', 1)->first();
        $record = $this->registry->snapshot()->modules[$context->module] ?? null;
        if ($state === null || $state->update_operation !== $context->operation || $state->update_owner !== $context->owner
            || $state->update_fingerprint !== $context->reservationFingerprint || $record === null
            || ! $record->registered || ! $record->sourcePresent || $record->manifestHash !== $context->manifestHash) {
            throw new \RuntimeException('Permissions preparation reservation changed.');
        }
        $audit = $this->registry->snapshot()->modules['runa.audit'] ?? throw new \RuntimeException('Audit source unavailable.');
        $writer = $this->declarations->make($audit, 'Runa\\Modules\\Audit\\Storage\\AppendEvents', AuditWriter::class);
        if (! $writer instanceof AuditWriter) {
            throw new \RuntimeException('Required Audit writer unavailable.');
        }
        $this->sync->apply($context->connection, $this->sync->preview($context->module), $writer);
    }
}
