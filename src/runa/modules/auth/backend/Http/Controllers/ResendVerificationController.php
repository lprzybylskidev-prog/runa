<?php

namespace Runa\Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Runa\Modules\Auth\Actions\EntryTokens;

final readonly class ResendVerificationController
{
    public function __construct(private EntryTokens $tokens) {}

    public function __invoke(Request $request): JsonResponse
    {
        $this->tokens->resendVerification(Auth::guard('web')->id());

        return response()->json(['data' => ['accepted' => true]], 202)->header('Cache-Control', 'no-store');
    }
}
