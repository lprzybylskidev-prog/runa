<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

final class ReadinessController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::table('shared.jobs')->limit(1)->get(['id']);
        } catch (QueryException) {
            return response()->json(['status' => 'unavailable'], 503, ['Cache-Control' => 'no-store']);
        }

        return response()->json(['status' => 'ok'], 200, ['Cache-Control' => 'no-store']);
    }
}
