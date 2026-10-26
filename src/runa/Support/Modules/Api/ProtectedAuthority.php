<?php

namespace Runa\Support\Modules\Api;

use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Runa\Contracts\Modules\AuthorityGuard;
use Runa\Support\Modules\Data\AuthorityLock;
use Runa\Support\Modules\ModuleAvailability;
use Runa\Support\Modules\ModuleRegistry;

/** Lazy trusted lifecycle adapter; absent or stale Permissions never grants a bypass. */
final readonly class ProtectedAuthority implements AuthorityGuard
{
    public function __construct(private DatabaseManager $database, private AuthorityLock $lock,
        private ModuleRegistry $registry, private ModuleAvailability $availability, private ModuleDeclarations $declarations) {}

    public function assertMayRemove(Connection $connection, array $publicIds): void
    {
        if ($connection !== $this->database->connection()) {
            throw new \RuntimeException('Authority requires the default transaction connection.');
        }
        $this->lock->acquire($connection);
        $record = $this->registry->snapshot()->modules['runa.permissions'] ?? null;
        if ($record === null || ! $this->availability->statuses('runa.permissions', 'authority.guard')['runa.permissions']->available) {
            throw new \RuntimeException('Trusted administrative authority integration unavailable.');
        }
        $guard = $this->declarations->make($record, 'Runa\\Modules\\Permissions\\Support\\PreserveAuthority', AuthorityGuard::class);
        if (! $guard instanceof AuthorityGuard) {
            throw new \RuntimeException('Trusted administrative authority integration unavailable.');
        }
        $guard->assertMayRemove($connection, $publicIds);
    }
}
