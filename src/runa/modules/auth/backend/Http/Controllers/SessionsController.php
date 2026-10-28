<?php

namespace Runa\Modules\Auth\Http\Controllers;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Runa\Modules\Auth\Actions\ManageSessions;
use Runa\Modules\Auth\Http\Requests\SessionsRequest;
use Runa\Modules\Auth\Support\Identifiers;

final readonly class SessionsController
{
    public function __construct(private ManageSessions $sessions) {}

    public function __invoke(SessionsRequest $request): JsonResponse
    {
        $rows = $this->sessions->listFor((Auth::guard('web')->user() ?? throw new AuthenticationException)->public_id, Identifiers::optionalText($request->validated('before')));
        foreach ($rows as &$row) {
            $row['current'] = $row['public_id'] === $request->session()->get('auth.session_id');
        }

        return response()->json(['data' => $rows, 'meta' => ['next_cursor' => count($rows) === 100 ? $rows[array_key_last($rows)]['public_id'] : null]], headers: ['Cache-Control' => 'no-store']);
    }
}
