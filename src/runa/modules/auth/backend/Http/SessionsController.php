<?php

namespace Runa\Modules\Auth\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Runa\Modules\Auth\Actions\ManageSessions;
use Runa\Modules\Auth\Http\Requests\SessionsRequest;

final readonly class SessionsController
{
    public function __construct(private ManageSessions $sessions) {}

    public function __invoke(SessionsRequest $request): JsonResponse
    {
        $rows = $this->sessions->listFor(Auth::guard('web')->user()->public_id, $request->validated('before'));
        foreach ($rows as &$row) {
            $row['current'] = $row['public_id'] === $request->session()->get('auth.session_id');
        }

        return response()->json(['data' => $rows, 'meta' => ['next_cursor' => count($rows) === 100 ? $rows[array_key_last($rows)]['public_id'] : null]], headers: ['Cache-Control' => 'no-store']);
    }
}
