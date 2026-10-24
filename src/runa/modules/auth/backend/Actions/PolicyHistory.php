<?php

namespace Runa\Modules\Auth\Actions;

use Illuminate\Database\Connection;
use Illuminate\Support\Str;
use Runa\Contracts\Modules\SettingsAuthorization;
use Runa\Contracts\Modules\SettingsContext;
use Runa\Contracts\Modules\SettingsParticipant;
use Runa\Contracts\Modules\SettingsPublication;
use Runa\Modules\Auth\Models\User;
use Runa\Support\Modules\Data\AuthorityLock;
use Runa\Support\Modules\Settings\ModuleSettings;

/** Synchronous required persistence behind the settings fence; never an observer. */
final readonly class PolicyHistory implements SettingsAuthorization, SettingsParticipant
{
    public function authorizing(Connection $connection, SettingsContext $context): void
    {
        if ($context->actor?->kind === 'actor') {
            app(AuthorityLock::class)->acquire($connection);
            app(StepUp::class)->assertVerified('runa.auth.policy.publish', 'both', everyAction: true);
        }
    }

    public function publishing(Connection $connection, SettingsPublication $publication): void
    {
        if ($publication->context->module !== 'runa.auth') {
            return;
        }
        if ($connection !== app('db')->connection() || $connection->transactionLevel() < 1) {
            throw new \LogicException('Policy history requires the publication transaction.');
        }
        app(AuthorityLock::class)->acquire($connection);
        $actor = $publication->context->actor;
        $state = app(ModuleSettings::class)->read('runa.auth');
        $values = array_map(fn ($setting) => $setting['value'], $state['settings']);
        $changes = array_filter($publication->changes, fn ($change) => ($change['old'] ?? null) !== ($change['new'] ?? null));
        $consequences = $this->consequences($changes, $values);
        if ($consequences['force_password_change']) {
            // A hash cannot prove which accounts meet a new complexity rule.
            $connection->table('runa_auth.users')->whereNull('deleted_at')->whereNotNull('password')
                ->update(['password_change_required' => true, 'version' => $connection->raw('version + 1'), 'updated_at' => now()]);
        }
        // Global revision invalidates exceptions even when their lifetime has not ended.
        $connection->table('runa_auth.policy_exceptions')->whereNull('revoked_at')->update(['revoked_at' => now(), 'version' => $connection->raw('version + 1'), 'updated_at' => now()]);
        $connection->table('runa_auth.policy_versions')->insert([
            'public_id' => (string) Str::uuid7(), 'revision' => $publication->revision,
            'operation' => $publication->context->operationId, 'phase' => $publication->phase,
            'values' => json_encode($values, JSON_THROW_ON_ERROR), 'changes' => json_encode($publication->changes, JSON_THROW_ON_ERROR),
            'consequences' => json_encode($consequences, JSON_THROW_ON_ERROR),
            'actor' => $actor === null ? null : json_encode(['kind' => $actor->kind, 'id' => $actor->id], JSON_THROW_ON_ERROR),
            'created_at' => now(),
        ]);
    }

    public function baseline(Connection $connection): void
    {
        $state = app(ModuleSettings::class)->read('runa.auth');
        if ($connection->table('runa_auth.policy_versions')->where('revision', $state['revision'])->exists()) {
            return;
        }
        $connection->table('runa_auth.policy_versions')->insert([
            'public_id' => (string) Str::uuid7(), 'revision' => $state['revision'], 'operation' => 'installation', 'phase' => 'baseline',
            'values' => json_encode(array_map(fn ($s) => $s['value'], $state['settings']), JSON_THROW_ON_ERROR),
            'changes' => '{}', 'consequences' => '{}', 'created_at' => now(),
        ]);
    }

    public function consequences(array $changes, array $values): array
    {
        $complexity = ['password_min_length', 'password_mixed_case', 'password_numbers', 'password_symbols'];
        $force = $values['password_policy_effect'] === 'force_change' && array_intersect(array_keys($changes), $complexity) !== [];

        return ['force_password_change' => $force,
            'password_accounts' => $force ? User::whereNotNull('password')->count() : 0,
            'exceptions_revalidated' => true, 'existing_revocations_reversed' => false,
            'session_deadlines_rechecked' => true, 'factor_requirements_rechecked' => true];
    }
}
