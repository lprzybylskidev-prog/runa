<?php

namespace Runa\Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Runa\Modules\Auth\Actions\ManageSessions;
use Runa\Modules\Auth\Http\Requests\ActivityRequest;

final readonly class ActivityController
{
    public function __construct(private ManageSessions $sessions) {}

    public function __invoke(ActivityRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->sessions->activity($request, Auth::guard('web')->user())], headers: ['Cache-Control' => 'no-store']);
    }
}
