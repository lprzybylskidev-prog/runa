<?php

namespace Runa\Modules\Auth\Http;

use Illuminate\Http\JsonResponse;
use Runa\Modules\Auth\Actions\Passwords;
use Runa\Modules\Auth\Http\Requests\PasswordChangeRequest;

final readonly class PasswordChangeController
{
    public function __construct(private Passwords $action) {}

    public function __invoke(PasswordChangeRequest $request): JsonResponse
    {
        $data = $this->action->change($request);

        return response()->json(['data' => $data], 200, ['Cache-Control' => 'no-store']);
    }
}
