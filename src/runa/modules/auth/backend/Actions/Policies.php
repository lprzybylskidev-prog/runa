<?php

namespace Runa\Modules\Auth\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Runa\Contracts\Modules\SettingsActor;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\ModuleFramework\Api\PrincipalScope;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\ModuleFramework\Settings\SettingsDefinitions;
use Runa\Modules\Auth\Contracts\AuthPolicies;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Support\EntryTransaction;
use Runa\Modules\Auth\Support\PolicyExceptions;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Settings\ModuleSettings;

final readonly class Policies implements AuthPolicies
{
    public function __construct(private ModuleSettings $settings, private SettingsDefinitions $definitions,
        private ModuleRegistry $registry, private EntryTransaction $transactions, private PrincipalScope $principals,
        private PolicyHistory $history, private StepUp $stepUp) {}

    public function history(?int $before = null): array
    {
        /** @var Collection<int, \stdClass&object{actor: string|null, operation: string, public_id: string, revision: int, phase: string, values: string, changes: string, consequences: string, created_at: string}> $rows */
        $rows = app('db')->connection()->table('runa_auth.policy_versions')->when($before !== null, fn ($q) => $q->where('revision', '<', $before))
            ->orderByDesc('revision')->limit(50)->get();

        return array_values($rows->map(fn ($row) => [
            'actor' => $row->actor === null ? null : json_decode($row->actor, true, flags: JSON_THROW_ON_ERROR),
            'operation' => $row->operation, 'public_id' => $row->public_id, 'revision' => $row->revision, 'phase' => $row->phase,
            'values' => json_decode($row->values, true, flags: JSON_THROW_ON_ERROR),
            'changes' => json_decode($row->changes, true, flags: JSON_THROW_ON_ERROR),
            'consequences' => json_decode($row->consequences, true, flags: JSON_THROW_ON_ERROR), 'created_at' => $row->created_at,
        ])->all());
    }

    public function exceptions(string $publicId): array
    {
        new TrustedPrincipal($publicId);
        $user = User::withTrashed()->where('public_id', $publicId)->firstOrFail();

        /** @var Collection<int, \stdClass&object{public_id: string, key: string, value: int, reason: string, actor: string, version: int, policy_revision: int, expires_at: string|null, revoked_at: string|null}> $rows */
        $rows = app('db')->connection()->table('runa_auth.policy_exceptions')->where('user_id', $user->id)
            ->orderByDesc('id')->limit(50)->get(['public_id', 'key', 'value', 'reason', 'actor', 'version', 'policy_revision', 'expires_at', 'revoked_at']);

        return array_values($rows->map(fn ($row) => [
            'public_id' => $row->public_id, 'key' => $row->key, 'value' => $row->value, 'reason' => $row->reason,
            'actor' => $row->actor, 'version' => $row->version, 'policy_revision' => $row->policy_revision,
            'expires_at' => $row->expires_at, 'revoked_at' => $row->revoked_at,
        ])->all());
    }

    public function activeExceptions(string $publicId): array
    {
        new TrustedPrincipal($publicId);
        $user = User::withTrashed()->where('public_id', $publicId)->firstOrFail();

        /** @var Collection<int, \stdClass&object{public_id: string, key: string, value: int, reason: string, actor: string, version: int, policy_revision: int, expires_at: string|null, revoked_at: string|null}> $rows */
        $rows = app('db')->connection()->table('runa_auth.policy_exceptions')->where('user_id', $user->id)
            ->whereIn('key', PolicyExceptions::KEYS)->whereNull('revoked_at')->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->where('policy_revision', $this->settings->read('runa.auth')['revision'])->orderBy('key')->limit(count(PolicyExceptions::KEYS))->get(['public_id', 'key', 'value', 'reason', 'actor', 'version', 'policy_revision', 'expires_at', 'revoked_at']);

        return array_values($rows->map(fn ($row) => [
            'public_id' => $row->public_id, 'key' => $row->key, 'value' => $row->value, 'reason' => $row->reason,
            'actor' => $row->actor, 'version' => $row->version, 'policy_revision' => $row->policy_revision,
            'expires_at' => $row->expires_at, 'revoked_at' => $row->revoked_at,
        ])->all());
    }

    public function preview(array $patch, int $revision, string $reviewId): array
    {
        return $this->transactions->run(function () use ($patch, $revision, $reviewId): array {
            $state = $this->settings->read('runa.auth');
            if ($state['revision'] !== $revision || $state['review_id'] !== $reviewId) {
                throw new OperationRejected('policy_stale', 'runa.auth', 'policy.preview', 409);
            }
            [$definitions, $provider] = $this->definitions->for($this->registry->snapshot()->modules['runa.auth']);
            $old = $this->history->values($state['settings']);
            if ($patch === [] || array_diff_key($patch, $definitions) !== []) {
                throw new \InvalidArgumentException('Expected declared policy keys.');
            }
            $values = array_replace($old, $patch);
            $this->definitions->validate($definitions, $provider, $values);
            $changes = [];
            foreach ($patch as $key => $value) {
                if ($old[$key] !== $value) {
                    $changes[$key] = ['old' => $old[$key], 'new' => $value];
                }
            }

            return ['revision' => $revision, 'review_id' => $reviewId, 'changes' => $changes,
                'consequences' => $this->history->consequences($changes, $values)];
        });
    }

    public function publish(array $patch, int $revision, string $reviewId): array
    {
        $principal = $this->principals->current() ?? throw new OperationRejected('authentication_required', 'runa.auth', 'policy.publish', 401);
        $this->preview($patch, $revision, $reviewId);
        // Source-declared access consumes one proof inside native settings publication.
        $result = $this->settings->update('runa.auth', $patch, $revision, $reviewId, new SettingsActor('actor', $principal->publicId));

        return ['operation' => $result->operationId, 'status' => $result->status];
    }

    /** Trusted operator recovery may use this same immutable source without web authorization.
     *
     * @return array<string, mixed>
     */
    public function restoreValues(int $revision): array
    {
        /** @var \stdClass&object{values: string} $row */
        $row = app('db')->connection()->table('runa_auth.policy_versions')->where('revision', $revision)->first()
            ?? throw new \InvalidArgumentException('Policy version not found.');

        $values = json_decode($row->values, true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($values)) {
            throw new \RuntimeException('Invalid stored policy values.');
        }
        $settings = [];
        foreach ($values as $key => $value) {
            if (! is_string($key)) {
                throw new \RuntimeException('Invalid stored policy key.');
            }
            $settings[$key] = $value;
        }

        return $settings;
    }

    /** Private owning batch phase; the coordinator owns authority, proof and user locks. */
    public function revokeExceptionsLocked(Connection $connection, User $user): void
    {
        $rows = $connection->table('runa_auth.policy_exceptions')->where('user_id', $user->id)->whereNull('revoked_at')->orderBy('id')->lockForUpdate()->get(['id', 'key', 'value', 'expires_at', 'reason']);
        /** @var \stdClass&object{expires_at:?string} $row */
        foreach ($rows as $row) {
            $connection->table('runa_auth.policy_exceptions')->where('id', $row->id)->update(['revoked_at' => now(), 'version' => $connection->raw('version + 1'), 'updated_at' => now()]);
            $active = $row->expires_at === null || CarbonImmutable::parse($row->expires_at)->isFuture();
            $this->transactions->record('policy.exception_revoked', $user, [
                'policy_setting' => ['old' => $row->key, 'new' => $row->key],
                'policy_value' => ['old' => $active ? $row->value : null, 'new' => null],
                'valid_until' => ['old' => $active ? $row->expires_at : null, 'new' => null],
                'reason' => ['old' => $active ? $row->reason : null, 'new' => null],
            ]);
        }
    }

    public function exceptionVersions(string $publicId): array
    {
        new TrustedPrincipal($publicId);
        $user = User::withTrashed()->where('public_id', $publicId)->firstOrFail();
        $versions = [];
        /** @var \stdClass&object{key:string,version:int|string} $row */
        foreach (app('db')->connection()->table('runa_auth.policy_exceptions')->where('user_id', $user->id)->selectRaw('key, MAX(version) AS version')->groupBy('key')->get() as $row) {
            $versions[$row->key] = (int) $row->version;
        }

        return $versions;
    }

    public function revokeExceptions(string $publicId, int $expectedVersion): bool
    {
        new TrustedPrincipal($publicId);

        return $this->transactions->run(function (Connection $connection) use ($publicId, $expectedVersion): bool {
            $this->transactions->lockAuthority($connection);
            $this->stepUp->assertVerified('runa.auth.policy.exception', everyAction: true);
            $user = User::withTrashed()->where('public_id', $publicId)->lockForUpdate()->first();
            if ($user === null || $user->version !== $expectedVersion) {
                return false;
            }
            $this->revokeExceptionsLocked($connection, $user);

            return true;
        });
    }

    public function exception(string $publicId, string $key, ?int $value, string $reason, ?string $expiresAt, int $version, int $policyRevision): array
    {
        new TrustedPrincipal($publicId);
        if ($expiresAt !== null && ! preg_match('/\A[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(?:Z|\+00:00)\z/D', $expiresAt)) {
            throw new \InvalidArgumentException('Expected a UTC exception deadline.');
        }
        $expires = $expiresAt === null ? null : CarbonImmutable::parse($expiresAt)->utc();
        if (! in_array($key, PolicyExceptions::KEYS, true) || $version < 0 || $policyRevision < 0
            || trim($reason) === '' || strlen($reason) > 1000 || preg_match('/[\x00-\x1f]/', $reason)
            || ($expires !== null && ! $expires->isFuture())
            || ($value !== null && ($value < 1 || $value > ($key === 'password_expiry_days' ? 3650 : 43200)))) {
            throw new \InvalidArgumentException('Invalid bounded policy exception.');
        }

        return $this->transactions->run(function (Connection $connection) use ($publicId, $key, $value, $reason, $expires, $version, $policyRevision): array {
            $this->transactions->lockAuthority($connection);
            $this->stepUp->assertVerified('runa.auth.policy.exception', everyAction: true);
            if ($this->settings->read('runa.auth')['revision'] !== $policyRevision) {
                throw new OperationRejected('policy_stale', 'runa.auth', 'policy.exception', 409);
            }
            $user = User::where('public_id', $publicId)->lockForUpdate()->firstOrFail();
            /** @var (\stdClass&object{id: int, version: int, revoked_at: string|null, expires_at: string|null, value: int, reason: string})|null $row */
            $row = $connection->table('runa_auth.policy_exceptions')->where('user_id', $user->id)->where('key', $key)->orderByDesc('id')->first();
            if (($row->version ?? 0) !== $version) {
                throw new OperationRejected('exception_stale', 'runa.auth', 'policy.exception', 409);
            }
            if ($row !== null) {
                $connection->table('runa_auth.policy_exceptions')->where('id', $row->id)->update(['revoked_at' => now(), 'version' => $version + 1, 'updated_at' => now()]);
            }
            $id = null;
            if ($value !== null) {
                $id = (string) Str::uuid7();
                $connection->table('runa_auth.policy_exceptions')->insert([
                    'public_id' => $id, 'user_id' => $user->id, 'key' => $key, 'value' => $value,
                    'reason' => $reason, 'actor' => ($this->principals->current() ?? throw new \LogicException('Policy actor unavailable.'))->publicId, 'version' => $version + 1,
                    'policy_revision' => $policyRevision, 'expires_at' => $expires, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            $previous = $row !== null && $row->revoked_at === null && ($row->expires_at === null || CarbonImmutable::parse($row->expires_at)->isFuture()) ? $row : null;
            $this->transactions->record($value === null ? 'policy.exception_revoked' : 'policy.exception_set', $user, [
                'policy_setting' => ['old' => $key, 'new' => $key],
                'policy_value' => ['old' => $previous?->value, 'new' => $value],
                'valid_until' => ['old' => $previous?->expires_at, 'new' => $value === null ? null : $expires?->toIso8601String()],
                'reason' => ['old' => $previous?->reason, 'new' => $value === null ? null : $reason],
            ]);

            return ['public_id' => $id, 'version' => $version + 1];
        });
    }
}
