<?php

namespace App\Http\Controllers;

use App\Support\HttpErrorPage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class ErrorReviewController extends Controller
{
    public function __invoke(int $status): never
    {
        abort_unless(app()->environment('local', 'testing') && in_array($status, HttpErrorPage::STATUSES, true), 404);
        abort($status, 'Private review exception details.', in_array($status, [429, 503], true) ? ['Retry-After' => '120'] : []);
    }

    public function emergency(Request $request): Response
    {
        abort_unless(app()->environment('local', 'testing'), 404);
        $locale = $request->query('lang') === 'en' ? 'en' : 'pl';
        $theme = $request->query('theme');
        $theme = in_array($theme, ['light', 'dark'], true) ? $theme : 'system';
        $path = public_path('runa-error/pages/503-'.$locale.'-'.$theme.'.html');
        abort_unless(is_file($path), 404);

        $content = file_get_contents($path);
        abort_unless(is_string($content), 404);

        return response($content, 503, ['Content-Type' => 'text/html; charset=UTF-8',
            'Retry-After' => '120', 'Cache-Control' => 'no-store, private']);
    }
}
