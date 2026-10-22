<?php

namespace Runa\Support\Modules\Console;

use Illuminate\Console\Application;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Connection;
use Illuminate\Support\Str;
use Runa\Contracts\Modules\CommandDefinition;
use Runa\Contracts\Modules\CommandProvider;
use Runa\Contracts\Modules\DiagnosticHandler;
use Runa\Contracts\Modules\ScheduleDefinition;
use Runa\Contracts\Modules\ScheduleProvider;
use Runa\Support\Logging\LogContext;
use Runa\Support\Modules\Api\BoundaryValues;
use Runa\Support\Modules\Api\ModuleDeclarations;
use Runa\Support\Modules\ModuleRecord;
use Runa\Support\Modules\ModuleRegistry;
use Runa\Support\Modules\Work\ModuleWork;
use Runa\Support\Modules\Work\WorkDefinitions;
use RuntimeException;
use Throwable;

/** Store only identities in wrappers; reacquire trusted source and classification at invocation. */
final readonly class ModuleConsole
{
    public function __construct(private ModuleRegistry $registry, private ModuleDeclarations $declarations, private WorkDefinitions $jobs, private ModuleWork $work, private Connection $connection, private LogContext $logs) {}

    /** @return array<string, CommandDefinition> */
    public function commands(ModuleRecord $record): array
    {
        $result = [];
        foreach ($this->declarations->classes($record, 'commands') as $class) {
            $provider = $this->declarations->make($record, $class, CommandProvider::class);
            if (! $provider instanceof CommandProvider) {
                throw new RuntimeException('Invalid command provider.');
            }
            foreach ($provider->commands() as $definition) {
                if (isset($result[$definition->key])) {
                    throw new RuntimeException('Duplicate module command.');
                }
                if ($definition->classification === 'operational') {
                    $this->jobs->resolve($record->id, $definition->target);
                } else {
                    $type = $this->declarations->inspect($record, $definition->target);
                    if (! $type->isInstantiable() || ! $type->implementsInterface(DiagnosticHandler::class)) {
                        throw new RuntimeException('Invalid diagnostic handler.');
                    }
                }
                $result[$definition->key] = $definition;
            }
        }

        return $result;
    }

    /** @param array<array-key, mixed> $payload
     * @return array<array-key, mixed>
     */
    public function execute(string $module, string $key, array $payload = []): array
    {
        $id = (string) Str::uuid7();

        return $this->logs->run(['origin' => $module, 'operation_id' => $id,
            'correlation_id' => $this->logs->current()['correlation_id'] ?? $id], fn (): array => $this->invoke($module, $key, $payload));
    }

    /** @param array<array-key, mixed> $payload
     * @return array<array-key, mixed>
     */
    private function invoke(string $module, string $key, array $payload): array
    {
        BoundaryValues::check($payload, "\0");
        if (strlen(json_encode($payload, JSON_THROW_ON_ERROR)) > 65536) {
            throw new RuntimeException('Module payload too large.');
        }
        $record = $this->registry->snapshot()->modules[$module] ?? throw new RuntimeException('Unknown module.');
        $definition = $this->commands($record)[$key] ?? throw new RuntimeException('Unknown module command.');
        if ($definition->classification === 'operational') {
            return ['work_id' => $this->work->dispatch($module, $definition->target, $payload), 'status' => 'pending'];
        }
        $handler = $this->declarations->make($record, $definition->target, DiagnosticHandler::class);
        if (! $handler instanceof DiagnosticHandler) {
            throw new RuntimeException('Invalid diagnostic handler.');
        }
        $result = $handler->diagnose($payload);
        BoundaryValues::check($result, "\0");
        if (strlen(json_encode($result, JSON_THROW_ON_ERROR)) > 65536) {
            throw new RuntimeException('Module diagnostic too large.');
        }

        return $result;
    }

    /** Cold setup and stale module source must not prevent recovery commands. */
    private function ready(): bool
    {
        try {
            return $this->connection->getSchemaBuilder()->hasTable('shared.module_registry_state');
        } catch (Throwable) {
            return false;
        }
    }

    public function registerCommands(Application $artisan): void
    {
        if (! $this->ready()) {
            return;
        }
        foreach ($this->registry->snapshot()->modules as $record) {
            if (! $record->registered || ! $record->sourcePresent) {
                continue;
            }
            try {
                $definitions = $this->commands($record);
            } catch (Throwable) {
                continue;
            }
            foreach ($definitions as $definition) {
                $name = str_replace('.', ':', $record->id).':'.$definition->key;
                if ($artisan->has($name)) {
                    throw new RuntimeException('Module command name collision.');
                }
                $artisan->add(new ModuleCommand($this, $record->id, $definition->key));
            }
        }
    }

    /** @return list<ScheduleDefinition> */
    private function schedules(ModuleRecord $record): array
    {
        $commands = $this->commands($record);
        $result = [];
        foreach ($this->declarations->classes($record, 'schedules') as $class) {
            $provider = $this->declarations->make($record, $class, ScheduleProvider::class);
            if (! $provider instanceof ScheduleProvider) {
                throw new RuntimeException('Invalid schedule provider.');
            }
            foreach ($provider->schedules() as $definition) {
                if (isset($result[$definition->key]) || ! isset($commands[$definition->command])) {
                    throw new RuntimeException('Invalid or duplicate schedule target.');
                }
                $result[$definition->key] = $definition;
            }
        }

        return array_values($result);
    }

    public function registerSchedules(Schedule $schedule): void
    {
        if (! $this->ready()) {
            return;
        }
        foreach ($this->registry->snapshot()->modules as $record) {
            if (! $record->registered || ! $record->sourcePresent) {
                continue;
            }
            try {
                $definitions = $this->schedules($record);
            } catch (Throwable) {
                continue;
            }
            foreach ($definitions as $definition) {
                $event = $schedule->call(function () use ($record, $definition): void {
                    // Revalidate the actual scheduled definition too; old scheduler objects cannot execute changed tasks.
                    $current = $this->registry->snapshot()->modules[$record->id] ?? throw new RuntimeException('Module missing.');
                    if ($current->manifestHash !== $record->manifestHash || ! in_array($definition, $this->schedules($current))) {
                        throw new RuntimeException('Scheduled source changed.');
                    }
                    $this->execute($record->id, $definition->command);
                })->name('module:'.$record->id.':'.$definition->key)->cron($definition->expression)->timezone('UTC');
                // Operational callbacks still run admission during maintenance and are refused there.
                // Diagnostics may perform health recovery while the platform is unavailable.
                $event->evenInMaintenanceMode();
                if ($definition->overlapMinutes !== null) {
                    $event->withoutOverlapping($definition->overlapMinutes);
                }
            }
        }
    }
}
