<?php

namespace Runa\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Runa\Http\Resources\ModuleFrontendResource;
use Runa\Support\Modules\Frontend\ModuleFrontend;

final class ModuleFrontendController extends Controller
{
    public function __invoke(ModuleFrontend $frontend): JsonResponse
    {
        try {
            $view = $frontend->projection();
        } catch (\Throwable) {
            return response()->json(['code' => 'module_frontend_unavailable'], 503)->header('Cache-Control', 'no-store, private');
        }

        return (new ModuleFrontendResource($view))->response()->header('Cache-Control', 'no-store, private');
    }
}
