<?php

namespace Runa\Modules\Auth\Http;

use Illuminate\Http\JsonResponse;
use Runa\Modules\Auth\Actions\Passwords;
use Runa\Modules\Auth\Http\Requests\PasswordResetRequest;

final readonly class PasswordResetController
{
    public function __construct(private Passwords $action) {}

    public function __invoke(PasswordResetRequest $request): JsonResponse
    {
        $this->action->reset($request->validated('token'), $request->validated('password'));
        $data = ['accepted' => true];

        return response()->json(['data' => $data], 200, ['Cache-Control' => 'no-store']);
    }
}
