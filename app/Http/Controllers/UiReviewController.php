<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class UiReviewController extends Controller
{
    public function __invoke(): BinaryFileResponse
    {
        // Also fail closed if a local route cache is accidentally reused elsewhere.
        abort_unless(app()->environment('local', 'testing'), 404);
        abort_unless(is_file(public_path('ui-review/index.html')), 404);

        return response()->file(public_path('ui-review/index.html'), [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
        ])->setPrivate();
    }
}
