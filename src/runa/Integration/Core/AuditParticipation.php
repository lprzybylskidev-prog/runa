<?php

namespace Runa\Integration\Core;

use Illuminate\Database\Connection;
use Runa\Contracts\Modules\OperationalParticipant;
use Runa\Contracts\Modules\PreparationContext;
use Runa\Contracts\Modules\PreparationParticipant;
use Runa\Contracts\Modules\SettingsParticipant;
use Runa\Contracts\Modules\SettingsPublication;
use Runa\ModuleFramework\Api\ModuleDeclarations;
use Runa\ModuleFramework\ModuleRegistry;

/** Framework-only reserved adapter; ordinary ingress always uses ModuleApis. */
final readonly class AuditParticipation implements OperationalParticipant, PreparationParticipant, SettingsParticipant
{
    public function __construct(private ModuleRegistry $registry, private ModuleDeclarations $declarations, private AdminOperationalAuthority $operations) {}

    public function recording(Connection $connection, string $module, string $operation, array $context): void
    {
        $this->operations->assertCurrent();
        $participant = $this->participant($connection);
        if ($participant !== null) {
            if (! $participant instanceof OperationalParticipant) {
                throw new \RuntimeException('Required Audit operational participation unavailable.');
            }
            $participant->recording($connection, $module, $operation, $context);
        }
    }

    public function publishing(Connection $connection, SettingsPublication $publication): void
    {
        $this->operations->assertCurrent();
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
        // Installed Permissions participates through its own trusted preparation boundary.
        // Initial Permissions installation synchronizes all sources in its owning handler.
        if ($context->connection->table('shared.modules')->where('module_id', 'runa.permissions')->whereNotNull('installed_version')->exists()) {
            $record = $this->registry->snapshot()->modules['runa.permissions'] ?? throw new \RuntimeException('Required Permissions source unavailable.');
            $permissions = $this->declarations->make($record, 'Runa\\Modules\\Permissions\\Participation', PreparationParticipant::class);
            if (! $permissions instanceof PreparationParticipant) {
                throw new \RuntimeException('Required Permissions preparation unavailable.');
            }
            $permissions->preparing($context);
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
