<?php

// External owner bootstrap. Never registered in customer Artisan or copied into runtime images.
use Illuminate\Contracts\Console\Kernel;
use Runa\Database\DatabaseScope;
use Runa\Database\RequiredSeeds;
use Symfony\Component\Console\Input\ArgvInput;

$root = getcwd();
if (is_file($root.'/src/vendor/autoload.php')) {
    $root .= '/src';
}
if (! is_file($root.'/vendor/autoload.php')) {
    throw new RuntimeException('Run the owner database entrypoint from the prepared application checkout.');
}
chdir($root);
require $root.'/vendor/autoload.php';
try {
    $app = require $root.'/bootstrap/app.php';
    $app->instance(DatabaseScope::class, new DatabaseScope($root, true));
    $command = $argv[1] ?? '';
    if ($command === 'install-seeds') {
        $release = $argv[2] ?? '';
        if (! preg_match('/\A[a-f0-9]{40}\z/D', $release) || count($argv) !== 3) {
            throw new RuntimeException('Platform seed initialization requires an exact release commit.');
        }
        $app->make(Kernel::class)->bootstrap();
        RequiredSeeds::run($app, $release);
        fwrite(STDOUT, "PASS: required platform seeds recorded atomically.\n");
        exit(0);
    }
    if (! in_array($command, ['migrate', 'migrate:status', 'migrate:fresh', 'migrate:refresh', 'migrate:reset', 'migrate:rollback', 'db:wipe', 'db:seed'], true)) {
        throw new RuntimeException('Unsupported owner database operation.');
    }
    exit($app->handleCommand(new ArgvInput($argv)));
} catch (Throwable) {
    fwrite(STDERR, "Owner database operation failed; inspect the selected source and database state.\n");
    exit(1);
}
