<?php

// Narrow forward-only primitive for explicit local preparation and deployment.
// No Artisan registration, owner reset/rollback/seed selector or HTTP entrypoint.
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Runa\Database\DatabaseScope;
use Runa\Database\RequiredSeeds;
use Symfony\Component\Console\Output\ConsoleOutput;

try {
    if (count($argv) !== 2 || preg_match('/\A[a-f0-9]{40}\z/D', $argv[1]) !== 1) {
        throw new RuntimeException('Forward data preparation requires one exact source commit.');
    }
    $root = dirname(__DIR__, 2);
    require $root.'/vendor/autoload.php';
    $app = require $root.'/bootstrap/app.php';
    if (! $app instanceof Application) {
        throw new RuntimeException('Invalid application bootstrap.');
    }
    $app->instance(DatabaseScope::class, new DatabaseScope($root, true));
    $kernel = $app->make(Kernel::class);
    $kernel->bootstrap();
    if ($kernel->call('migrate', ['--force' => true, '--no-interaction' => true], new ConsoleOutput) !== 0) {
        throw new RuntimeException('Forward platform migration failed.');
    }
    RequiredSeeds::run($app, $argv[1]);
    fwrite(STDOUT, "PASS: forward platform data preparation completed.\n");
} catch (Throwable) {
    fwrite(STDERR, "Forward platform data preparation failed; preserve operation evidence and inspect migration/seed state.\n");
    exit(1);
}
