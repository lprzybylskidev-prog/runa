<?php

namespace Runa\Modules\Auth\Support;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Runa\Modules\Auth\Models\User;
use Runa\Support\Modules\Settings\ModuleSettings;

/** Only allowlisted bounded lifetime values, with optional override expiry; never relax 2FA, eligibility, step-up or identity. */
final readonly class PolicyExceptions
{
    public const KEYS = ['password_expiry_days', 'session_idle_minutes'];

    public function __construct(private ModuleSettings $settings) {}

    /** Private correlated query for native directory/session policy reads. */
    public function queryFor(string $key, string $userColumn): Builder
    {
        if (! in_array($key, self::KEYS, true)) {
            throw new \InvalidArgumentException('Unknown exception key.');
        }
        $connection = app('db')->connection();

        return $connection->table('runa_auth.policy_exceptions')->select('value')
            ->whereColumn('user_id', $userColumn)->where('key', $key)
            ->where('policy_revision', $connection->table('shared.modules')->where('module_id', 'runa.auth')->value('settings_revision'))
            ->whereNull('revoked_at')->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', CarbonImmutable::now('UTC')))->orderByDesc('id')->limit(1);
    }

    public function value(User $user, string $key): int
    {
        return $this->values([$user], $key)[$user->id];
    }

    /** Current overrides for an already locked bounded account batch.
     * @param  list<User>  $users
     * @return array<int,int>
     */
    public function values(array $users, string $key): array
    {
        $global = $this->settings->integer('runa.auth', $key);
        $connection = app('db')->connection();
        $revision = $connection->table('shared.modules')->where('module_id', 'runa.auth')->value('settings_revision');
        $ids = array_map(static fn (User $user): int => $user->id, $users);
        /** @var array<int,int> $overrides */
        $overrides = $connection->table('runa_auth.policy_exceptions')->whereIn('user_id', $ids)->where('key', $key)
            ->where('policy_revision', $revision)->whereNull('revoked_at')->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', CarbonImmutable::now('UTC')))->pluck('value', 'user_id')->all();

        return array_replace(array_fill_keys($ids, $global), $overrides);
    }
}
