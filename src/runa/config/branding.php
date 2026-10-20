<?php

$version = file_get_contents(dirname(__DIR__, 3).'/VERSION');
if ($version === false) {
    throw new RuntimeException('Application VERSION is missing.');
}
$version = trim($version);

// The same light palette is imported by the Vue application's CSS.
$paletteSource = file_get_contents(resource_path('css/light-palette.css'));
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
    'application_id' => 'runa',
    'application_name' => 'Runa',
    'application_version' => $version,
    'platform_version' => $version,
    // A trusted local PNG/JPEG path for CID, or a credential-free public HTTPS URL.
    'mail_logo_path' => resource_path('images/runa-mail-logo.png'),
    'mail_logo_url' => null,
    'mail_logo_alt' => null,
    'mail_footer' => ['visible' => true, 'application' => true, 'platform' => true],
];
