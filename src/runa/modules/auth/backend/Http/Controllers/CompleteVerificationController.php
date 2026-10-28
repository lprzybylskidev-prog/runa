<?php

namespace Runa\Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Runa\Modules\Auth\Actions\EntryTokens;
use Runa\Modules\Auth\Http\Requests\TokenRequest;

final readonly class CompleteVerificationController
{
    public function __construct(private EntryTokens $tokens) {}

    public function __invoke(TokenRequest $request): JsonResponse
    {
        $this->tokens->consume($request->validated('token'), 'verification');

        return response()->json(['data' => ['accepted' => true]])->header('Cache-Control', 'no-store');
    }
}
