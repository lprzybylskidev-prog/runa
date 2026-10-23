<?php

namespace Runa\Modules\Auth\Http;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Runa\Modules\Auth\Actions\SessionStore;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Queries\LookupIdentity;
use Runa\Support\Modules\Settings\ModuleSettings;

/** Own-session projection only; credentials and internal IDs never serialize. */
final class SessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this->resource;
        if (! $user instanceof User) {
            throw new \LogicException('Auth session requires an owned user.');
        }
        $eligibility = app(LookupIdentity::class)->evaluate($user, CarbonImmutable::now('UTC'));

        return ['public_id' => $user->public_id, 'display_name' => $user->identity()->displayName, 'restrictions' => $eligibility->restrictions,
            'session' => app(SessionStore::class)->projection(
                app(SessionStore::class)->current($request, $user) ?? throw new \LogicException('Session unavailable.'), true),
            'activity' => ['heartbeat_seconds' => app(ModuleSettings::class)->value('runa.auth', 'session_heartbeat_seconds'),
                'idle_minutes' => app(ModuleSettings::class)->value('runa.auth', 'session_idle_minutes'),
                'warning_seconds' => app(ModuleSettings::class)->value('runa.auth', 'session_warning_seconds'),
                'extension' => app(ModuleSettings::class)->value('runa.auth', 'session_extension')]];
    }
}
