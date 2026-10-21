<?php

use Runa\Support\HttpErrorPage;

// Build standalone assets without booting Laravel, loading env files or accessing a database.
require dirname(__DIR__, 3).'/vendor/autoload.php';

$root = dirname(__DIR__, 3);
$output = $argv[1] ?? $root.'/public/runa-error';
if (! is_dir($output.'/pages') && ! mkdir($output.'/pages', 0775, true) && ! is_dir($output.'/pages')) {
    throw new RuntimeException('Cannot create error assets directory.');
}
if (! is_file($output.'/styles.css') || ! is_dir($root.'/.runtime/runa-errors')) {
    throw new RuntimeException('Build the shared MinimalLayout error shells with pnpm run build:errors first.');
}
$branding = require $root.'/runa/config/branding.php';
$renderer = new HttpErrorPage;
foreach (['pl', 'en'] as $locale) {
    foreach (['system', 'light', 'dark'] as $theme) {
        extract($renderer->data(503, $locale, $theme, $branding['application_name'], $branding['application_version'], $branding['platform_version']), EXTR_OVERWRITE);
        ob_start();
        require $root.'/runa/resources/views/errors/page.php';
        $html = ob_get_clean();
        if (! is_string($html)) {
            throw new RuntimeException('Cannot render standalone HTTP error page.');
        }
        writeErrorAsset($output.'/pages/503-'.$locale.'-'.$theme.'.html', $html);
    }
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
    return dirname(__DIR__, 3).($path !== '' ? '/'.$path : '');
}

function resource_path(string $path = ''): string
{
    return base_path('runa/resources').($path !== '' ? '/'.$path : '');
}
