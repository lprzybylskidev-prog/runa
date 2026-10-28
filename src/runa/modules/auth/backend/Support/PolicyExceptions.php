<?php

namespace Runa\Modules\Auth\Support;

use Carbon\CarbonImmutable;
use Runa\Modules\Auth\Models\User;
use Runa\Support\Modules\Settings\ModuleSettings;

/** Only bounded lifetime exceptions; never relax 2FA, eligibility, step-up or identity. */
final readonly class PolicyExceptions
{
    public const KEYS = ['password_expiry_days', 'session_idle_minutes'];

    public function __construct(private ModuleSettings $settings) {}

    public function value(User $user, string $key): int
    {
        $global = $this->settings->integer('runa.auth', $key);
        $connection = app('db')->connection();
        $revision = $connection->table('shared.modules')->where('module_id', 'runa.auth')->value('settings_revision');
        /** @var (\stdClass&object{value: int})|null $row */
        $row = $connection->table('runa_auth.policy_exceptions')->where('user_id', $user->id)->where('key', $key)
            ->where('policy_revision', $revision)->whereNull('revoked_at')->where('expires_at', '>', CarbonImmutable::now('UTC'))->first();

        return $row === null ? $global : $row->value;
    }
}
