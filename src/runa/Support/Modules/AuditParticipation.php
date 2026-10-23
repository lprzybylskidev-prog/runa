<?php

namespace Runa\Support\Modules;

use Illuminate\Database\Connection;
use Runa\Contracts\Modules\PreparationContext;
use Runa\Contracts\Modules\PreparationParticipant;
use Runa\Contracts\Modules\SettingsParticipant;
use Runa\Contracts\Modules\SettingsPublication;
use Runa\Support\Modules\Api\ModuleDeclarations;

/** Framework-only reserved adapter; ordinary ingress always uses ModuleApis. */
final readonly class AuditParticipation implements PreparationParticipant, SettingsParticipant
{
    public function __construct(private ModuleRegistry $registry, private ModuleDeclarations $declarations) {}

    public function publishing(Connection $connection, SettingsPublication $publication): void
    {
        $participant = $this->participant($connection);
        if ($participant !== null) {
            if (! $participant instanceof SettingsParticipant) {
                throw new \RuntimeException('Required Audit settings participation unavailable.');
            }
            $participant->publishing($connection, $publication);
        }
    }

    public function preparing(PreparationContext $context): void
    {
        $participant = $this->participant($context->connection);
        if ($participant !== null) {
            if (! $participant instanceof PreparationParticipant) {
                throw new \RuntimeException('Required Audit preparation participation unavailable.');
            }
            $participant->preparing($context);
        }
    }

    private function participant(Connection $connection): ?object
    {
        if ($connection->transactionLevel() < 1 || $connection->getDriverName() !== 'pgsql') {
            throw new \RuntimeException('Audit participation requires a PostgreSQL transaction.');
        }
        if (! $connection->table('shared.modules')->where('module_id', 'runa.audit')->whereNotNull('installed_version')->exists()) {
            return null;
        }
        $record = $this->registry->snapshot()->modules['runa.audit'] ?? throw new \RuntimeException('Required Audit source unavailable.');

        return $this->declarations->make($record, 'Runa\\Modules\\Audit\\Participation');
    }
}
