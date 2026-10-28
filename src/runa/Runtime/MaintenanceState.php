<?php

namespace Runa\Runtime;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Connection;
use Runa\Contracts\Modules\GraphBlocker;
use Runa\ModuleFramework\ModuleAvailability;
use Runa\ModuleFramework\ModuleRegistry;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Throwable;

/** Independent derived reasons; never writes or clears Laravel/updater maintenance artifacts. */
final readonly class MaintenanceState
{
    public function __construct(private Application $app, private Connection $connection, private ModuleRegistry $registry, private ModuleAvailability $availability) {}

    /** Trusted diagnostics only. Public readiness exposes only an unavailable status.
     * @return array<string, list<GraphBlocker>>
     */
    public function reasons(): array
    {
        $reasons = [];
        if ($this->app->maintenanceMode()->active()) {
            $reasons['manual'] = [];
        }
        $marker = $this->app->storagePath('framework/maintenance/enabled');
        if (is_file($marker) || is_link($marker)) {
            $reasons['update'] = [];
        }
        try {
            $this->connection->table('shared.jobs')->limit(1)->get(['id']);
            // Evaluate each required Core graph once. No nonexistent production Core is invented.
            $snapshot = $this->registry->snapshot();
            $core = array_filter($snapshot->modules, fn ($record): bool => $record->kind === 'core');
            if ($core !== []) {
                foreach ($this->availability->statuses(array_keys($core), '*') as $id => $status) {
                    if (isset($core[$id]) && ! $status->available) {
                        $reasons['core:'.$id] = $status->reasons;
                    }
                }
            }
        } catch (Throwable) {
            $reasons['runtime'] = [];
        }

        return $reasons;
    }

    public function active(): bool
    {
        return $this->reasons() !== [];
    }

    public function assertOperational(): void
    {
        if ($this->active()) {
            throw new ServiceUnavailableHttpException(null, 'Service Unavailable');
        }
    }
}
