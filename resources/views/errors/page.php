<!doctype html>
<html lang="<?= htmlspecialchars($locale, ENT_QUOTES, 'UTF-8') ?>" data-theme="<?= htmlspecialchars($theme, ENT_QUOTES, 'UTF-8') ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= $status ?> · <?= htmlspecialchars($copy['statuses'][(string) $status][0], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></title>
<link rel="icon" href="/favicon.ico" sizes="16x16 32x32 48x48">
<link rel="stylesheet" href="/runa-error/styles.css">
</head>
<body>
<?php
$variant = $name === 'Runa' ? 'runa' : 'custom';
$template = file_get_contents(__DIR__.'/generated/'.$status.'-'.$locale.'-'.$theme.'-'.$variant.'.html');
if ($template === false) {
    throw new RuntimeException('Pre-rendered MinimalLayout error template is missing.');
}
echo strtr($template, [
    '__RUNA_APPLICATION_NAME__' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
    '__RUNA_APPLICATION_VERSION__' => htmlspecialchars($version, ENT_QUOTES, 'UTF-8'),
    '__RUNA_PLATFORM_VERSION__' => htmlspecialchars($platformVersion, ENT_QUOTES, 'UTF-8'),
    '__RUNA_REQUEST_ID__' => htmlspecialchars($requestId, ENT_QUOTES, 'UTF-8'),
    '__RUNA_YEAR__' => date('Y'),
]);
?>
</body>
</html>
