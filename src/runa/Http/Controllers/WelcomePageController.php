<?php

namespace Runa\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Runa\Support\Modules\ModuleAvailability;
use Runa\Support\Modules\Settings\ModuleSettings;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Default public entry; presentation policy never authenticates a visitor. */
final readonly class WelcomePageController
{
    public function __invoke(): BinaryFileResponse|RedirectResponse
    {
        $available = app(ModuleAvailability::class)->statuses('runa.auth')['runa.auth'] ?? null;
        if ($available?->available && app(ModuleSettings::class)->value('runa.auth', 'public_welcome_enabled') === false) {
            return redirect()->route('runa.auth.pages.login')->header('Cache-Control', 'no-store');
        }

        return response()->file(public_path('build/index.html'), [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    }
}
