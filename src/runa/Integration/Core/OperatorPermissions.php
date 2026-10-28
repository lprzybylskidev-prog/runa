<?php

namespace Runa\Integration\Core;

use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Runa\ModuleFramework\Api\ModuleDeclarations;
use Runa\ModuleFramework\Data\AuthorityLock;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\Modules\Auth\Contracts\OperatorAuthority;

/** Trusted local recovery: source/data/fences are mandatory; account/policy health is recoverable. */
final readonly class OperatorPermissions implements OperatorAuthority
{
    public function __construct(private DatabaseManager $database, private ModuleRegistry $registry,
        private ModuleDeclarations $declarations, private AuthorityLock $lock) {}

    public function initialize(Connection $connection, string $publicId): void
    {
        $this->participant($connection)->initialize($connection, $publicId);
    }

    public function isAdministrator(Connection $connection, string $publicId): bool
    {
        return $this->participant($connection)->isAdministrator($connection, $publicId);
    }

    private function participant(Connection $connection): OperatorAuthority
    {
        if ($connection !== $this->database->connection()) {
            throw new \RuntimeException('Operator authority requires the default transaction connection.');
        }
        $this->lock->acquire($connection);
        $snapshot = $this->registry->snapshot();
        foreach (['runa.audit', 'runa.auth', 'runa.permissions'] as $id) {
            $record = $snapshot->modules[$id] ?? null;
            if ($record === null || ! $record->registered || ! $record->sourcePresent || ! $record->enabled
                || $record->installedVersion !== $record->sourceVersion || $snapshot->fenced !== []) {
                throw new \RuntimeException('Complete Core source/data preparation before operator recovery.');
            }
        }
        $participant = $this->declarations->make($snapshot->modules['runa.permissions'],
            'Runa\\Modules\\Permissions\\Support\\OperatorAuthority', OperatorAuthority::class);
        if (! $participant instanceof OperatorAuthority) {
            throw new \RuntimeException('Operator authority unavailable.');
        }

        return $participant;
    }
}
