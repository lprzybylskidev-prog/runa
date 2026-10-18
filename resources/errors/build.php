<?php

use App\Support\HttpErrorPage;

// Build standalone assets without booting Laravel, loading env files or accessing a database.
require __DIR__.'/../../app/Support/HttpErrorPage.php';

$root = dirname(__DIR__, 2);
$output = $argv[1] ?? $root.'/public/runa-error';
if (! is_dir($output.'/pages') && ! mkdir($output.'/pages', 0775, true) && ! is_dir($output.'/pages')) {
    throw new RuntimeException('Cannot create error assets directory.');
}
foreach ([
    '/resources/errors/styles.css' => 'styles.css',
    '/resources/css/light-palette.css' => 'light-palette.css',
    '/resources/images/runa-logo.png' => 'logo.png',
    '/resources/fonts/IBMPlexMono-Regular.woff2' => 'regular.woff2',
    '/resources/fonts/IBMPlexMono-SemiBold.woff2' => 'semibold.woff2',
    '/resources/fonts/LICENSE.txt' => 'font-license.txt',
] as $source => $target) {
    if (! copy($root.$source, $output.'/'.$target)) {
        throw new RuntimeException('Cannot copy error asset.');
    }
}
$css = file_get_contents($root.'/resources/css/app.css');
if ($css === false || ! preg_match("/:root\\[data-theme='dark'\\]\\s*\\{(.*?)\\n\\}/s", $css, $dark)) {
    throw new RuntimeException('Cannot read shared dark palette.');
}
preg_match_all('/--runa-[a-z-]+:\s*#[a-fA-F0-9]{6};/', $dark[1], $tokens);
$dark = implode("\n", $tokens[0]);
writeErrorAsset($output.'/dark-palette.css', ':root[data-theme="dark"] {'.$dark.'} @media(prefers-color-scheme:dark) { :root:not([data-theme="light"]) {'.$dark.'} }');
$branding = require $root.'/config/branding.php';
$renderer = new HttpErrorPage;
foreach (['pl', 'en'] as $locale) {
    extract($renderer->data(503, $locale, 'system', $branding['application_name'], $branding['application_version'], $branding['platform_version']), EXTR_OVERWRITE);
    ob_start();
    require $root.'/resources/views/errors/page.php';
    $html = ob_get_clean();
    if (! is_string($html)) {
        throw new RuntimeException('Cannot render standalone HTTP error page.');
    }
    writeErrorAsset($output.'/pages/503-'.$locale.'.html', $html);
}
writeErrorAsset($output.'/pages/503.json', json_encode(['message' => 'Service Unavailable', 'code' => 'service_unavailable'], JSON_THROW_ON_ERROR));
fwrite(STDOUT, "Built standalone HTTP error assets.\n");

function writeErrorAsset(string $path, string $content): void
{
    if (file_put_contents($path, $content) === false) {
        throw new RuntimeException('Cannot write standalone HTTP error asset.');
    }
}

// Path-only helpers let the shared, non-secret branding configuration load without Laravel.
function base_path(string $path = ''): string
{
    return dirname(__DIR__, 2).($path !== '' ? '/'.$path : '');
}

function resource_path(string $path = ''): string
{
    return base_path('resources').($path !== '' ? '/'.$path : '');
}
