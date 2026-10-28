<?php

namespace Runa\Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Runa\Modules\Auth\Actions\EmailAddresses;
use Runa\Modules\Auth\Http\Requests\TokenRequest;

final readonly class EmailCompleteController
{
    public function __construct(private EmailAddresses $action) {}

    public function __invoke(TokenRequest $request): JsonResponse
    {
        $this->action->complete($request->string('token')->toString());

        return response()->json(['data' => ['completed' => true]], 200, ['Cache-Control' => 'no-store']);
    }
}
