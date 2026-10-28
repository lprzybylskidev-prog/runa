<?php

namespace Runa\ModuleFramework;

use Illuminate\Database\Connection;
use Runa\Contracts\Modules\GraphBlocker;
use Runa\Contracts\Modules\HealthContext;
use Runa\Contracts\Modules\HealthDefinition;
use Runa\Support\Modules\ModuleRecord;
use RuntimeException;
use Throwable;

/** Persist bounded observations without retaining effective state across clock/generation changes. */
final class ModuleHealth
{
    /** @var array<string, true> */
    private static array $running = [];

    public function __construct(private readonly Connection $connection, private readonly ModuleRegistry $registry, private readonly HealthPolicies $policies, private readonly HealthDeadline $deadline) {}

    /** Trusted management write with optimistic concurrency; source policy validates every override.
     * @param  array<string, mixed>  $overrides
     */
    public function configure(string $id, array $overrides, int $generation): void
    {
        $encoded = json_encode((object) $overrides, JSON_THROW_ON_ERROR);
        if (strlen($encoded) > 4096) {
            throw new RuntimeException('Health overrides exceed the safe bound.');
        }
        $this->connection->transaction(function () use ($id, $overrides, $generation, $encoded): void {
            $this->connection->table('shared.module_registry_state')->where('id', 1)->lockForUpdate()->first();
            $snapshot = $this->registry->snapshot();
            if ($snapshot->generation !== $generation || $snapshot->fenced !== []) {
                throw new RuntimeException('Refresh health configuration after the current transition.');
            }
            $record = $snapshot->modules[$id] ?? throw new RuntimeException('Unknown module.');
            $policy = $this->policies->resolve($record);
            if ($policy === null) {
                throw new RuntimeException('The module declares no health overrides.');
            }
            $policy->definition($overrides);
            $this->connection->table('shared.modules')->where('module_id', $id)->update([
                'health_overrides' => $encoded, 'health_revision' => $record->healthRevision + 1,
                'health_observations' => '{}', 'updated_at' => now(),
            ]);
            $this->connection->table('shared.module_registry_state')->where('id', 1)->increment('generation');
        });
    }

    /** Explicit trusted diagnosis also prepares observations before enabling a disabled module.
     * @return array{state: string, reasons: list<GraphBlocker>, blocked: bool, observations: ?array<string, array{fingerprint: string, passed: bool, checked_at: int, expires_at: int, code: string}>}
     */
    public function diagnose(string $id): array
    {
        $record = $this->registry->snapshot()->modules[$id] ?? throw new RuntimeException('Unknown module.');

        return $this->inspect($record, null, 'diagnostic');
    }

    /** Diagnostic recovery deliberately ignores administrative/effective business gates. */
    public function recover(): void
    {
        foreach ($this->registry->snapshot()->modules as $record) {
            if ($record->registered && $record->sourcePresent && $record->enabled) {
                $this->inspect($record, null, 'scheduled');
            }
        }
    }

    /** @return array{state: string, reasons: list<GraphBlocker>, blocked: bool, observations: ?array<string, array{fingerprint: string, passed: bool, checked_at: int, expires_at: int, code: string}>} */
    public function inspect(ModuleRecord $record, ?string $operation = null, string $trigger = 'cached'): array
    {
        try {
            $policy = $this->policies->resolve($record);
            $definition = $policy?->definition($record->healthOverrides) ?? new HealthDefinition('none', 'scheduled', []);
            $checks = array_filter($definition->checks, fn ($check): bool => $operation === null || $check->applies($operation));
            $fingerprint = hash('sha256', json_encode([$record->manifestHash, $record->healthRevision, $definition], JSON_THROW_ON_ERROR));
            $observations = $record->healthObservations;
            $run = $policy !== null && ($trigger === 'diagnostic' || ($trigger === 'use' && in_array($definition->mode, ['on_use', 'hybrid'], true)) || ($trigger === 'scheduled' && in_array($definition->mode, ['scheduled', 'hybrid'], true)));
            if ($run && $checks !== []) {
                // Session advisory locks release on worker death; they have no expiring lease overlap.
                // A contending operation cannot use a fresh cache to skip its required on-use check.
                if ($this->connection->transactionLevel() !== 0) {
                    throw new RuntimeException('Health checks require an independent transaction boundary.');
                }
                if (isset(self::$running[$record->id])) {
                    return ['state' => 'unknown', 'reasons' => [new GraphBlocker('health_busy', [$record->id])], 'blocked' => true, 'observations' => null];
                }
                $key = (int) hexdec(substr(hash('sha256', $record->id), 0, 7));
                /** @var object{acquired: bool} $lock */
                $lock = $this->connection->selectOne('SELECT pg_try_advisory_lock(73006, ?) AS acquired', [$key]);
                if (! $lock->acquired) {
                    return ['state' => 'unknown', 'reasons' => [new GraphBlocker('health_busy', [$record->id])], 'blocked' => true, 'observations' => null];
                }
                self::$running[$record->id] = true;
                try {
                    $fresh = $this->registry->snapshot()->modules[$record->id] ?? null;
                    if ($fresh === null || ! $this->samePolicy($record, $fresh)) {
                        throw new RuntimeException('Health policy changed.');
                    }
                    $observations = $fresh->healthObservations;
                    $changed = false;
                    foreach ($checks as $check) {
                        $previous = $observations[$check->id] ?? null;
                        if ($trigger === 'scheduled' && $previous !== null && $previous['fingerprint'] === $fingerprint && $previous['checked_at'] <= now()->getTimestamp() && now()->getTimestamp() < $previous['checked_at'] + $check->interval) {
                            continue;
                        }
                        $started = now()->getTimestamp();
                        $code = 'health_failed';
                        try {
                            $passed = $this->deadline->run($check->timeout, fn (): bool => $policy->check($check->id, new HealthContext($record->id, $operation, $trigger, microtime(true) + $check->timeout)));
                            $code = $passed ? 'healthy' : 'health_failed';
                        } catch (Throwable) {
                            $passed = false;
                            $code = 'health_check_error';
                        }
                        $observations[$check->id] = ['fingerprint' => $fingerprint, 'passed' => $passed, 'checked_at' => $started, 'expires_at' => $started + $check->expiry, 'code' => $code];
                        $changed = true;
                    }
                    if ($changed) {
                        // Discard removed definitions and reject old-source/policy results at publication.
                        $observations = array_intersect_key($observations, array_fill_keys(array_map(fn ($check): string => $check->id, $definition->checks), true));
                        $this->connection->transaction(function () use ($record, $observations): void {
                            $this->connection->table('shared.module_registry_state')->where('id', 1)->lockForUpdate()->first();
                            $current = $this->registry->snapshot()->modules[$record->id] ?? null;
                            if ($current === null || ! $this->samePolicy($record, $current)) {
                                throw new RuntimeException('Health observation became stale.');
                            }
                            $this->connection->table('shared.modules')->where('module_id', $record->id)->update(['health_observations' => json_encode((object) $observations, JSON_THROW_ON_ERROR), 'updated_at' => now()]);
                            $this->connection->table('shared.module_registry_state')->where('id', 1)->increment('generation');
                        });
                    }
                } finally {
                    unset(self::$running[$record->id]);
                    $this->connection->selectOne('SELECT pg_advisory_unlock(73006, ?)', [$key]);
                }
            }
            $state = 'healthy';
            $blocked = false;
            $reasons = [];
            foreach ($checks as $check) {
                $observation = $observations[$check->id] ?? null;
                $stale = $observation === null || $observation['fingerprint'] !== $fingerprint || $observation['checked_at'] > now()->getTimestamp() || $observation['expires_at'] <= now()->getTimestamp();
                if ($stale || ! $observation['passed']) {
                    $reasons[] = new GraphBlocker($stale ? 'health_expired' : $observation['code'], [$record->id, $check->id]);
                    if ($check->severity === 'critical') {
                        $blocked = true;
                        if ($state !== 'unhealthy') {
                            $state = $stale ? 'unknown' : 'unhealthy';
                        }
                    } elseif ($state === 'healthy') {
                        $state = 'degraded';
                    }
                }
            }

            return ['state' => $state, 'reasons' => $reasons, 'blocked' => $blocked, 'observations' => $observations];
        } catch (Throwable) {
            return ['state' => 'unknown', 'reasons' => [new GraphBlocker('health_policy_unavailable', [$record->id])], 'blocked' => true, 'observations' => null];
        }
    }

    private function samePolicy(ModuleRecord $old, ModuleRecord $new): bool
    {
        return $new->registered && $new->sourcePresent && $new->publicId === $old->publicId && $new->manifestHash === $old->manifestHash && $new->healthRevision === $old->healthRevision;
    }
}
