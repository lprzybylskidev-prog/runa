<?php

namespace Runa\Modules\Auth\Http\Controllers;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Runa\Modules\Auth\Actions\TrustedDevices;
use Runa\Modules\Auth\Http\Requests\SessionRevokeRequest;

final readonly class TrustRevokeController
{
    public function __construct(private TrustedDevices $devices) {}

    public function __invoke(SessionRevokeRequest $request): JsonResponse
    {
        $this->devices->revoke((Auth::guard('web')->user() ?? throw new AuthenticationException)->public_id, $request->string('public_id')->toString());

        return response()->json(['data' => ['accepted' => true]], headers: ['Cache-Control' => 'no-store']);
    }
}
