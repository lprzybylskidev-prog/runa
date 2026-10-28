<?php

namespace Runa\Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Runa\Modules\Auth\Actions\AuthenticateSession;
use Runa\Modules\Auth\Http\Requests\LoginRequest;
use Runa\Modules\Auth\Http\Resources\SessionResource;

final readonly class LoginController
{
    public function __construct(private AuthenticateSession $sessions) {}

    public function __invoke(LoginRequest $request): JsonResponse
    {
        return (new SessionResource($this->sessions->login($request)))->response()->header('Cache-Control', 'no-store');
    }
}
