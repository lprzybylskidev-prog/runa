<?php

namespace Runa\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Runa\Support\Modules\Frontend\ModuleFrontend;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ModulePageController extends Controller
{
    public function __invoke(Request $request, ModuleFrontend $frontend): BinaryFileResponse
    {
        $route = $request->route();
        $name = $route instanceof Route ? $route->getName() : null;
        $found = false;
        foreach ($frontend->projection()['pages'] as $page) {
            if ($page['serverRouteName'] === $name) {
                $found = true;
            }
        }
        abort_unless($found, 404);

        return response()->file(public_path('build/index.html'), ['Content-Type' => 'text/html; charset=UTF-8', 'Cache-Control' => 'no-cache']);
    }
}
