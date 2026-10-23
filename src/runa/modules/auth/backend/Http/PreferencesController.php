<?php

namespace Runa\Modules\Auth\Http;

use Illuminate\Database\Connection;
use Illuminate\Http\JsonResponse;
use Runa\Modules\Auth\Actions\AccountPreferences;
use Runa\Modules\Auth\Http\Requests\PreferenceRequest;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Support\SecurityContext;

final readonly class PreferencesController
{
    public function __construct(private SecurityContext $context, private AccountPreferences $preferences) {}

    public function __invoke(PreferenceRequest $request): JsonResponse
    {
        $data = $this->context->run($request, fn (Connection $connection, User $user): array => $this->preferences->http($connection, $user, $request->validated()), count: false, unrestricted: true);

        return (new ProfileResource($data))->response()->header('Cache-Control', 'no-store');
    }
}
