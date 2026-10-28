<?php

namespace Runa\Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Runa\Modules\Auth\Http\Resources\SessionResource;

final readonly class SessionController
{
    public function __invoke(Request $request): JsonResponse
    {
        return (new SessionResource(Auth::guard('web')->user()))->response()->header('Cache-Control', 'no-store');
    }
}
