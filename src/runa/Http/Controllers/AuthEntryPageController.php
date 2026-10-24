<?php

namespace Runa\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Presentation only. Every data operation still enters the guarded module API. */
final readonly class AuthEntryPageController
{
    public function __invoke(): BinaryFileResponse
    {
        return response()->file(public_path('build/index.html'), [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-store',
            'Referrer-Policy' => 'no-referrer',
        ]);
    }
}
