<?php

namespace Runa\Modules\Auth\Http;

use Illuminate\Http\JsonResponse;
use Runa\Modules\Auth\Actions\AuthenticateSession;
use Runa\Modules\Auth\Http\Requests\LoginRequest;

final readonly class LoginController
{
    public function __construct(private AuthenticateSession $sessions) {}

    public function __invoke(LoginRequest $request): JsonResponse
    {
        return (new SessionResource($this->sessions->login($request)))->response()->header('Cache-Control', 'no-store');
    }
}
