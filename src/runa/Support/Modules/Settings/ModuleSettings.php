<?php

namespace Runa\Support\Modules\Settings;

use Closure;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Database\Connection;
use Illuminate\Support\Str;
use Runa\Contracts\Modules\SettingChange;
use Runa\Contracts\Modules\SettingDefinition;
use Runa\Contracts\Modules\SettingsActor;
use Runa\Contracts\Modules\SettingsContext;
use Runa\Contracts\Modules\SettingsObserver;
use Runa\Contracts\Modules\TransitionResult;
use Runa\Support\Modules\ModuleRecord;
use Runa\Support\Modules\ModuleRegistry;
use RuntimeException;
use Throwable;

/** Trusted management service. No authorization fiction, HTTP writes or public secret projection.
 * @phpstan-type State object{settings_values: ?string, settings_revision: int}
 * @phpstan-type Operation object{public_id: string, intent: string, target: string, generation: int, payload: string, status: string, publication_generation: ?int}
 * @phpstan-type Payload array{old: ?string, candidate: string, revision: int, manifest: string, schema: string, handler: string, application: string, changes: array<string, array<string, mixed>>, actor: ?array{kind: string, id: string}, occurred_at: string}
 */
final class ModuleSettings
{
    private static bool $running = false;

    private ?int $runnerPid = null;

    public function __construct(
        private readonly Connection $connection, private readonly ModuleRegistry $registry,
        private readonly SettingsDefinitions $definitions, private readonly Encrypter $encrypter,
        private readonly SettingsObserver $observer,
    ) {}

    /** Safe management projection. Sensitive values expose set-state only, never ciphertext.
     * @return array{generation: int, revision: int, settings: array<string, array{definition: SettingDefinition, value?: mixed, set?: bool}>}
     */
    public function read(string $module): array
    {
        return $this->connection->transaction(function () use ($module): array {
            $this->connection->table('shared.module_registry_state')->where('id', 1)->sharedLock()->first();
            $snapshot = $this->registry->snapshot();
            $record = $snapshot->modules[$module] ?? throw new RuntimeException('Unknown module.');
            [$definitions, $provider] = $this->definitions->for($record);
            $state = $this->state($module);
            $values = $this->values($state->settings_values, $definitions);
            $this->definitions->validate($definitions, $provider, $values);
            $settings = [];
            foreach ($definitions as $key => $definition) {
                $settings[$key] = ['definition' => $definition, ...($definition->sensitive ? ['set' => $values[$key] !== null] : ['value' => $values[$key]])];
            }

            return ['generation' => $snapshot->generation, 'revision' => $state->settings_revision, 'settings' => $settings];
        });
    }

    /** Explicit private runtime access for trusted owning-module code; never serialize this result.
     * Operational entrypoints must still pass the availability guard. Handlers can read behind their fence.
     */
    public function value(string $module, string $key): mixed
    {
        return $this->connection->transaction(function () use ($module, $key): mixed {
            $this->connection->table('shared.module_registry_state')->where('id', 1)->sharedLock()->first();
            $record = $this->registry->snapshot()->modules[$module] ?? throw new RuntimeException('Unknown module.');
            [$definitions, $provider] = $this->definitions->for($record);
            if (! isset($definitions[$key])) {
                throw new RuntimeException('Unknown setting.');
            }
            $values = $this->values($this->state($module)->settings_values, $definitions);
            $this->definitions->validate($definitions, $provider, $values);

            return $values[$key];
        });
    }

    /** Patch declared keys; omitted values are retained. Sensitive null explicitly clears a value.
     * @param  array<string, mixed>  $patch
     */
    public function update(string $module, #[\SensitiveParameter] array $patch, int $expectedRevision, int $expectedGeneration, ?SettingsActor $actor = null): TransitionResult
    {
        return $this->exclusive(function () use ($module, $patch, $expectedRevision, $expectedGeneration, $actor): TransitionResult {
            $id = (string) Str::uuid7();
            $this->connection->transaction(function () use ($id, $module, $patch, $expectedRevision, $expectedGeneration, $actor): void {
                $this->lock();
                $snapshot = $this->registry->snapshot();
                $state = $this->state($module);
                if ($snapshot->generation !== $expectedGeneration || $state->settings_revision !== $expectedRevision || $this->connection->table('shared.module_transitions')->whereIn('status', ModuleRegistry::ACTIVE_TRANSITIONS)->exists()) {
                    throw new RuntimeException('Settings revision changed or transition unresolved.');
                }
                $record = $snapshot->modules[$module] ?? throw new RuntimeException('Unknown module.');
                [$definitions, $provider, $handler] = $this->definitions->for($record);
                if ($patch === [] || array_diff_key($patch, $definitions) !== []) {
                    throw new RuntimeException('Expected declared setting keys.');
                }
                $old = $this->values($state->settings_values, $definitions);
                $this->definitions->validate($definitions, $provider, $old);
                $candidate = array_replace($old, $patch);
                $this->definitions->validate($definitions, $provider, $candidate);
                $changes = [];
                $application = 'immediate';
                foreach ($patch as $key => $value) {
                    $definition = $definitions[$key];
                    $changes[$key] = $definition->sensitive ? ['replaced' => true] : ['old' => $old[$key], 'new' => $value];
                    if ($definition->application === 'reinitialize') {
                        $application = 'reinitialize';
                    }
                }
                $encrypted = $this->encode($candidate);
                $payload = [
                    'old' => $state->settings_values, 'candidate' => $encrypted, 'revision' => $expectedRevision + 1,
                    'manifest' => $record->manifestHash, 'schema' => $this->schema($definitions), 'handler' => $handler,
                    'application' => $application, 'changes' => $changes,
                    'actor' => $actor === null ? null : ['kind' => $actor->kind, 'id' => $actor->id],
                    'occurred_at' => now()->utc()->toIso8601String(),
                ];
                $this->connection->table('shared.module_transitions')->insert([
                    'public_id' => $id, 'intent' => 'settings', 'target' => $module, 'generation' => $expectedGeneration,
                    'affected' => json_encode([$module], JSON_THROW_ON_ERROR), 'payload' => $this->encode($payload),
                    'status' => 'preparing', 'created_at' => now(), 'updated_at' => now(),
                ]);
                $this->write($module, $encrypted, $expectedRevision + 1);
                $this->bump(); // Publish candidate and durable fence together, preserving enabled state.
            });
            $row = $this->operation($id);
            $payload = $this->payload($row);
            try {
                $record = $this->current($row, $payload);
                if (! $this->definitions->handler($record, $payload['handler'])->apply($this->context($row, $payload))) {
                    throw new RuntimeException('Settings application refused.');
                }
                $this->assertRunner();
                $this->current($row, $payload);
                $this->finish($row, 'succeeded');
            } catch (Throwable) {
                if ($this->operation($id)->status !== 'succeeded') {
                    return $this->rollback($row, $payload);
                }
            }
            $this->notify($row, $payload, 'succeeded');

            return $this->status($id);
        });
    }

    /** Interrupted application is never blindly replayed. Conservatively restore and compensate. */
    public function recover(string $id): TransitionResult
    {
        return $this->exclusive(function () use ($id): TransitionResult {
            $row = $this->operation($id);
            if (! in_array($row->status, ModuleRegistry::ACTIVE_TRANSITIONS, true)) {
                return $this->status($id);
            }

            return $this->rollback($row, $this->payload($row));
        });
    }

    public function status(string $id): TransitionResult
    {
        $row = $this->operation($id);

        return new TransitionResult($id, in_array($row->status, ['preparing', 'compensating'], true) ? 'pending' : $row->status, $row->publication_generation, [$row->target => $row->status === 'recovery_required' ? 'settings_compensation_required' : 'settings_'.$row->status]);
    }

    /** @param Operation $row
     * @param  Payload  $payload
     */
    private function rollback(object $row, array $payload): TransitionResult
    {
        try {
            $this->assertRunner();
            // Refuse replacing state after unexpected external writes; keep the existing fence.
            $this->connection->transaction(function () use ($row, $payload): void {
                $this->lock();
                $this->current($row, $payload, true);
                $state = $this->state($row->target);
                if ($state->settings_values !== $payload['old']) {
                    $this->write($row->target, $payload['old'], $state->settings_revision + 1);
                }
                $this->save($row->public_id, 'compensating');
            });
            $record = $this->current($row, $payload, true);
            if (! $this->definitions->handler($record, $payload['handler'])->compensate($this->context($row, $payload))) {
                throw new RuntimeException('Settings compensation incomplete.');
            }
            $this->finish($row, 'failed');
            $outcome = 'failed';
        } catch (Throwable) {
            $this->assertRunner();
            $this->save($row->public_id, 'recovery_required');
            $outcome = 'recovery_required';
        }
        $this->notify($row, $payload, $outcome);

        return $this->status($row->public_id);
    }

    /** @param Operation $row
     * @param  Payload  $payload
     */
    private function current(object $row, array $payload, bool $rollback = false): ModuleRecord
    {
        $this->assertRunner();
        $snapshot = $this->registry->snapshot();
        $record = $snapshot->modules[$row->target] ?? throw new RuntimeException('Settings source missing.');
        [$definitions, , $handler] = $this->definitions->for($record);
        $state = $this->state($row->target);
        $candidate = $state->settings_values === $payload['candidate'] && $state->settings_revision === $payload['revision'];
        $restored = $rollback && $state->settings_values === $payload['old'] && $state->settings_revision === $payload['revision'] + 1;
        if ($snapshot->generation !== $row->generation + 1 || $record->manifestHash !== $payload['manifest'] || $handler !== $payload['handler'] || $this->schema($definitions) !== $payload['schema'] || (! $candidate && ! $restored)) {
            throw new RuntimeException('Settings reservation or source changed.');
        }

        return $record;
    }

    /** @param Operation $row */
    private function finish(object $row, string $status): void
    {
        $this->connection->transaction(function () use ($row, $status): void {
            $this->assertRunner();
            $this->lock();
            $payload = $this->payload($row);
            $this->current($row, $payload, $status === 'failed');
            // Release old/candidate credentials once recovery is no longer necessary.
            unset($payload['old'], $payload['candidate']);
            $this->connection->table('shared.modules')->where('module_id', $row->target)->update(['health_observations' => '{}']);
            $this->bump();
            $this->connection->table('shared.module_transitions')->where('public_id', $row->public_id)->update(['status' => $status, 'payload' => $this->encode($payload), 'publication_generation' => $row->generation + 2, 'updated_at' => now()]);
        });
    }

    /** @param Operation $row
     * @param  Payload  $payload
     */
    private function context(object $row, array $payload): SettingsContext
    {
        $actor = $payload['actor'];

        return new SettingsContext($row->public_id, $row->target, $payload['revision'], $payload['application'], $payload['changes'], $actor === null ? null : new SettingsActor($actor['kind'], $actor['id']), $payload['occurred_at']);
    }

    /** @param Operation $row
     * @param  Payload  $payload
     */
    private function notify(object $row, array $payload, string $outcome): void
    {
        $context = $this->context($row, $payload);
        foreach ($payload['changes'] as $key => $change) {
            try {
                $this->observer->changed(new SettingChange($context, $key, $change, $outcome));
            } catch (Throwable) {
                // Independent future integration failure never changes the settings outcome or leaks payloads.
            }
        }
    }

    /** @return State */
    private function state(string $module): object
    {
        /** @var State|null $row */
        $row = $this->connection->table('shared.modules')->where('module_id', $module)->first(['settings_values', 'settings_revision']);

        return $row ?? throw new RuntimeException('Unknown module settings.');
    }

    /** @param array<string, SettingDefinition> $definitions
     * @return array<string, mixed>
     */
    private function values(?string $encrypted, array $definitions): array
    {
        return $encrypted === null ? array_map(fn (SettingDefinition $definition): mixed => $definition->default, $definitions) : $this->decode($encrypted);
    }

    private function write(string $module, ?string $encrypted, int $revision): void
    {
        $this->connection->table('shared.modules')->where('module_id', $module)->update(['settings_values' => $encrypted, 'settings_revision' => $revision, 'health_observations' => '{}', 'updated_at' => now()]);
    }

    /** @param array<string, SettingDefinition> $definitions */
    private function schema(array $definitions): string
    {
        return hash('sha256', json_encode($definitions, JSON_THROW_ON_ERROR));
    }

    /** @return Operation */
    private function operation(string $id): object
    {
        /** @var Operation|null $row */
        $row = $this->connection->table('shared.module_transitions')->where('public_id', $id)->where('intent', 'settings')->first();

        return $row ?? throw new RuntimeException('Unknown settings operation.');
    }

    /** @param Operation $row
     * @return Payload
     */
    private function payload(object $row): array
    {
        /** @var Payload $payload */
        $payload = $this->decode($row->payload);

        return $payload;
    }

    /** @param array<string, mixed> $values */
    private function encode(#[\SensitiveParameter] array $values): string
    {
        try {
            return $this->encrypter->encrypt(json_encode($values, JSON_THROW_ON_ERROR), false);
        } catch (Throwable) {
            throw new RuntimeException('Settings encryption unavailable.');
        }
    }

    /** @return array<string, mixed> */
    private function decode(string $encrypted): array
    {
        try {
            $json = $this->encrypter->decrypt($encrypted, false);
            if (! is_string($json)) {
                throw new RuntimeException;
            }
            $decoded = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
            if (! is_array($decoded)) {
                throw new RuntimeException;
            }
            $values = [];
            foreach ($decoded as $key => $value) {
                if (! is_string($key)) {
                    throw new RuntimeException;
                }
                $values[$key] = $value;
            }

            return $values;
        } catch (Throwable) {
            throw new RuntimeException('Settings key or encrypted data unavailable; restore the external key before recovery.');
        }
    }

    private function save(string $id, string $status): void
    {
        $this->assertRunner();
        $this->connection->table('shared.module_transitions')->where('public_id', $id)->update(['status' => $status, 'updated_at' => now()]);
    }

    private function lock(): void
    {
        $this->connection->table('shared.module_registry_state')->where('id', 1)->lockForUpdate()->first();
    }

    private function bump(): void
    {
        $this->connection->table('shared.module_registry_state')->where('id', 1)->increment('generation');
    }

    private function assertRunner(): void
    {
        /** @var object{pid: int} $session */
        $session = $this->connection->selectOne('SELECT pg_backend_pid() AS pid');
        if ($session->pid !== $this->runnerPid) {
            throw new RuntimeException('Settings runner connection changed; recover using a new runner.');
        }
    }

    /** @param Closure(): TransitionResult $work */
    private function exclusive(Closure $work): TransitionResult
    {
        if (self::$running || $this->connection->transactionLevel() !== 0) {
            throw new RuntimeException('Settings application requires an independent runner outside a transaction.');
        }
        /** @var object{acquired: bool, pid: int} $lock */
        $lock = $this->connection->selectOne('SELECT pg_try_advisory_lock(1381322305, 4) AS acquired, pg_backend_pid() AS pid');
        if (! $lock->acquired) {
            throw new RuntimeException('Another module transition runner is active.');
        }
        self::$running = true;
        $this->runnerPid = $lock->pid;
        try {
            return $work();
        } finally {
            self::$running = false;
            $this->runnerPid = null;
            $this->connection->selectOne('SELECT pg_advisory_unlock(1381322305, 4)');
        }
    }
}
