<?php

namespace Runa\Support\Modules;

use Illuminate\Database\Connection;
use Runa\Contracts\Modules\PreparationContext;
use Runa\Contracts\Modules\PreparationParticipant;
use Runa\Contracts\Modules\SettingsAuthorization;
use Runa\Contracts\Modules\SettingsContext;
use Runa\Contracts\Modules\SettingsParticipant;
use Runa\Contracts\Modules\SettingsPublication;
use Runa\Support\Modules\Api\ModuleDeclarations;

/** Framework-only reserved adapter; ordinary ingress always uses ModuleApis. */
final readonly class AuditParticipation implements PreparationParticipant, SettingsAuthorization, SettingsParticipant
{
    public function __construct(private ModuleRegistry $registry, private ModuleDeclarations $declarations) {}

    public function authorizing(Connection $connection, SettingsContext $context): void
    {
        if ($context->module !== 'runa.auth') {
            return;
        }
        $version = $connection->table('shared.modules')->where('module_id', 'runa.auth')->value('installed_version');
        if (! is_string($version) || version_compare($version, '0.7.0', '<')) {
            return;
        }
        $record = $this->registry->snapshot()->modules['runa.auth'] ?? throw new \RuntimeException('Required Auth source unavailable.');
        $history = $this->declarations->make($record, 'Runa\\Modules\\Auth\\Actions\\PolicyHistory', SettingsAuthorization::class);
        if (! $history instanceof SettingsAuthorization) {
            throw new \RuntimeException('Required Auth policy authorization unavailable.');
        }
        $history->authorizing($connection, $context);
    }

    public function publishing(Connection $connection, SettingsPublication $publication): void
    {
        $participant = $this->participant($connection);
        if ($participant !== null) {
            if (! $participant instanceof SettingsParticipant) {
                throw new \RuntimeException('Required Audit settings participation unavailable.');
            }
            $participant->publishing($connection, $publication);
        }
        if ($publication->context->module === 'runa.auth') {
            $version = $connection->table('shared.modules')->where('module_id', 'runa.auth')->value('installed_version');
            // The 0.7 upgrade creates storage before publishing added defaults.
            $hasHistory = $connection->getSchemaBuilder()->hasTable('runa_auth.policy_versions');
            if ($hasHistory || (is_string($version) && version_compare($version, '0.7.0', '>='))) {
                $record = $this->registry->snapshot()->modules['runa.auth'] ?? throw new \RuntimeException('Required Auth policy source unavailable.');
                $history = $this->declarations->make($record, 'Runa\\Modules\\Auth\\Actions\\PolicyHistory', SettingsParticipant::class);
                if (! $history instanceof SettingsParticipant) {
                    throw new \RuntimeException('Required Auth policy participant unavailable.');
                }
                $history->publishing($connection, $publication);
            }
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
