<?php

namespace Runa\Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Runa\Modules\Auth\Actions\EntryTokens;
use Runa\Modules\Auth\Http\Requests\InvitationRequest;

final readonly class AcceptInvitationController
{
    public function __construct(private EntryTokens $tokens) {}

    public function __invoke(InvitationRequest $request): JsonResponse
    {
        $this->tokens->consume($request->string('token')->toString(), 'invitation', $request->string('password')->toString());

        return response()->json(['data' => ['accepted' => true]])->header('Cache-Control', 'no-store');
    }
}
