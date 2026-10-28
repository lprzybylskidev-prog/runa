<?php

namespace Runa\Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class CsrfController
{
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json(['data' => ['ready' => true]])->header('Cache-Control', 'no-store');
    }
}
