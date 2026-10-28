<?php

namespace Runa\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Runa\Runtime\MaintenanceState;

final class ReadinessController extends Controller
{
    public function __invoke(): JsonResponse
    {
        if (app(MaintenanceState::class)->active()) {
            return response()->json(['status' => 'unavailable'], 503, ['Cache-Control' => 'no-store']);
        }

        return response()->json(['status' => 'ok'], 200, ['Cache-Control' => 'no-store']);
    }
}
