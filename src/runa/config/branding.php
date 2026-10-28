<?php

use Runa\Identity\ApplicationManifest;
use Runa\Identity\PlatformManifest;

$platform = PlatformManifest::load(base_path('runa/platform.json'));
$version = $platform->version;
$manifest = ApplicationManifest::load(base_path('app/application.json'));
$manifest->assertCompatible($version);

// The same light palette is imported by the Vue application's CSS.
$paletteSource = file_get_contents(base_path('runa/resources/css/light-palette.css'));
if ($paletteSource === false) {
    throw new RuntimeException('Shared light palette is missing.');
}
preg_match_all('/--runa-([a-z-]+):\s*(#[a-fA-F0-9]{6});/', $paletteSource, $matches, PREG_SET_ORDER);
$colors = [];
foreach ($matches as $match) {
    $colors[$match[1]] = $match[2];
}

return [
    'colors' => $colors,
    'application_id' => $manifest->id,
    'application_name' => $manifest->name,
    'application_version' => $manifest->version,
    'logo' => $manifest->logo,
    'platform_version' => $version,
    // A trusted local PNG/JPEG path for CID, or a credential-free public HTTPS URL.
    'mail_logo_path' => base_path('runa/resources/images/runa-mail-logo.png'),
    'mail_logo_url' => $manifest->logo !== null && str_starts_with($manifest->logo['src'], 'https://') ? $manifest->logo['src'] : null,
    'mail_logo_alt' => $manifest->logo['accessibleName'] ?? null,
    'mail_footer' => ['visible' => true, 'application' => true, 'platform' => true],
];
