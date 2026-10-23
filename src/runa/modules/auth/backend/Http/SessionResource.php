<?php

namespace Runa\Modules\Auth\Http;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Queries\LookupIdentity;

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

        return ['public_id' => $user->public_id, 'display_name' => $user->identity()->displayName, 'restrictions' => $eligibility->restrictions];
    }
}
