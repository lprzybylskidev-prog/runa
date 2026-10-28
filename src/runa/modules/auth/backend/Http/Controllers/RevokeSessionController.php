<?php

namespace Runa\Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Runa\Modules\Auth\Actions\ManageSessions;
use Runa\Modules\Auth\Http\Requests\SessionRevokeRequest;

final readonly class RevokeSessionController
{
    public function __construct(private ManageSessions $sessions) {}

    public function __invoke(SessionRevokeRequest $request): JsonResponse
    {
        $this->sessions->revoke(Auth::guard('web')->user()->public_id, $request->validated('public_id'));

        return response()->json(['data' => ['accepted' => true]], headers: ['Cache-Control' => 'no-store']);
    }
}
