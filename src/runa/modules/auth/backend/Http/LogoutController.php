<?php

namespace Runa\Modules\Auth\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Runa\Modules\Auth\Actions\AuthenticateSession;

final readonly class LogoutController
{
    public function __construct(private AuthenticateSession $sessions) {}

    public function __invoke(Request $request): JsonResponse
    {
        $this->sessions->logout($request);

        return response()->json(['data' => ['accepted' => true]])->header('Cache-Control', 'no-store');
    }
}
