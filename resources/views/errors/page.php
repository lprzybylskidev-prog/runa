<!doctype html>
<html lang="<?= htmlspecialchars($locale, ENT_QUOTES, 'UTF-8') ?>" data-theme="<?= htmlspecialchars($theme, ENT_QUOTES, 'UTF-8') ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= $status ?> · <?= htmlspecialchars($copy['statuses'][(string) $status][0], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></title>
<link rel="stylesheet" href="/runa-error/styles.css">
</head>
<body>
<main class="error-page">
<section class="error-card" aria-labelledby="error-title">
<header class="error-header">
<a href="/" class="brand" aria-label="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>">
<span class="brand-mark" aria-hidden="true"><span class="runa-logo"></span></span>
<span><?php if ($name === 'Runa') { ?>runa<span class="brand-dot">.</span><?php } else { ?><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?><?php } ?></span>
</a>
<nav aria-label="<?= htmlspecialchars($copy['language'], ENT_QUOTES, 'UTF-8') ?>" class="languages">
<a href="?lang=pl" lang="pl" hreflang="pl" <?= $locale === 'pl' ? 'aria-current="true"' : '' ?>>PL</a>
<a href="?lang=en" lang="en" hreflang="en" <?= $locale === 'en' ? 'aria-current="true"' : '' ?>>EN</a>
</nav>
</header>
<div class="error-content">
<p class="status-number" aria-hidden="true"><?= $status ?></p>
<p class="error-code">HTTP <?= $status ?> / <?= htmlspecialchars($errorCode, ENT_QUOTES, 'UTF-8') ?></p>
<h1 id="error-title"><?= htmlspecialchars($copy['statuses'][(string) $status][0], ENT_QUOTES, 'UTF-8') ?></h1>
<p class="description"><?= htmlspecialchars($copy['statuses'][(string) $status][1], ENT_QUOTES, 'UTF-8') ?></p>
<div class="actions">
<a href="/" class="button button-primary"><?= htmlspecialchars($copy['home'], ENT_QUOTES, 'UTF-8') ?></a>
<?php if (in_array($status, [419, 429, 500, 503], true)) { ?>
<a href="" class="button button-secondary"><?= htmlspecialchars($copy['retry'], ENT_QUOTES, 'UTF-8') ?></a>
<?php } ?>
</div>
<?php if ($requestId !== '') { ?><p class="request-id">Request ID: <?= htmlspecialchars($requestId, ENT_QUOTES, 'UTF-8') ?></p><?php } ?>
</div>
</section>
<footer><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?> v<?= htmlspecialchars($version, ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars($copy['powered'], ENT_QUOTES, 'UTF-8') ?> v<?= htmlspecialchars($platformVersion, ENT_QUOTES, 'UTF-8') ?></footer>
</main>
</body>
</html>
