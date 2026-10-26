<?php

namespace Runa\Modules\Auth\Http;

use Illuminate\Http\JsonResponse;
use Runa\Modules\Auth\Actions\Passwords;
use Runa\Modules\Auth\Http\Requests\PasswordForgotRequest;

final readonly class PasswordForgotController
{
    public function __construct(private Passwords $action) {}

    public function __invoke(PasswordForgotRequest $request): JsonResponse
    {
        $this->action->requestReset($request->validated('identifier'));
        $data = ['accepted' => true];

        return response()->json(['data' => $data], 202, ['Cache-Control' => 'no-store']);
    }
}
