<?php

namespace Runa\Http\Controllers;

use Illuminate\Http\Response;
use Runa\Settings\SettingStore;

final class ApplicationAssetController extends Controller
{
    public function __invoke(string $kind, string $hash, SettingStore $store): Response
    {
        $bytes = $store->asset($kind, $hash);
        abort_if($bytes === null, 404);

        return response($bytes, 200, ['Content-Type' => 'image/png', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'public, max-age=31536000, immutable']);
    }
}
