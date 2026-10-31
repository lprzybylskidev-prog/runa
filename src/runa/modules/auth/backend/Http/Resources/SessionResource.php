<?php

namespace Runa\Modules\Auth\Http\Resources;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Runa\Modules\Auth\Actions\SessionStore;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Queries\LookupIdentity;
use Runa\Modules\Auth\Support\CredentialPolicy;
use Runa\Modules\Auth\Support\PolicyExceptions;
use Runa\Support\Modules\Settings\ModuleSettings;

/** Own-session projection only; credentials and internal IDs never serialize. */
final class SessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return DB::connection()->transaction(fn (): array => $this->project($request));
    }

    /** @return array<string,mixed> */
    private function project(Request $request): array
    {
        $user = $this->resource;
        if (! $user instanceof User) {
            throw new \LogicException('Auth session requires an owned user.');
        }
        $eligibility = app(LookupIdentity::class)->evaluate($user, CarbonImmutable::now('UTC'));
        $session = app(SessionStore::class)->current($request, $user) ?? throw new \LogicException('Session unavailable.');

        return ['public_id' => $user->public_id, 'display_name' => $user->identity()->displayName, 'restrictions' => array_values(array_unique(array_merge($eligibility->restrictions,
            app(CredentialPolicy::class)->restrictions($user, $session)))),
            'session' => app(SessionStore::class)->projection($session, true),
            'activity' => ['heartbeat_seconds' => app(ModuleSettings::class)->value('runa.auth', 'session_heartbeat_seconds'),
                'idle_minutes' => app(PolicyExceptions::class)->value($user, 'session_idle_minutes'),
                'warning_seconds' => app(ModuleSettings::class)->value('runa.auth', 'session_warning_seconds'),
                'extension' => app(ModuleSettings::class)->value('runa.auth', 'session_extension')]];
    }
}
