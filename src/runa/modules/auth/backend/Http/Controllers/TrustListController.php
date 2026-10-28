<?php

namespace Runa\Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Runa\Modules\Auth\Actions\TrustedDevices;
use Runa\Modules\Auth\Http\Requests\SessionsRequest;

final readonly class TrustListController
{
    public function __construct(private TrustedDevices $devices) {}

    public function __invoke(SessionsRequest $request): JsonResponse
    {
        $rows = $this->devices->listFor(Auth::guard('web')->id(), $request->validated('before'));

        return response()->json(['data' => $rows, 'meta' => ['next_cursor' => count($rows) === 100 ? $rows[array_key_last($rows)]['public_id'] : null]], headers: ['Cache-Control' => 'no-store']);
    }
}
