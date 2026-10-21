<?php

// Narrow forward-only primitive for explicit local preparation and deployment.
// No Artisan registration, owner reset/rollback/seed selector or HTTP entrypoint.
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Runa\Database\DatabaseScope;
use Runa\Database\RequiredSeeds;
use Symfony\Component\Console\Output\ConsoleOutput;

try {
    if (! in_array(count($argv), [2, 3], true) || preg_match('/\A[a-f0-9]{40}\z/D', $argv[1]) !== 1) {
        throw new RuntimeException('Forward data preparation requires one exact source commit.');
    }
    $phase = $argv[2] ?? 'all';
    if (! in_array($phase, ['all', 'migrations', 'required-seeds'], true)) {
        throw new RuntimeException('Unsupported forward preparation phase.');
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
    if ($phase !== 'required-seeds' && $kernel->call('migrate', ['--force' => true, '--no-interaction' => true], new ConsoleOutput) !== 0) {
        throw new RuntimeException('Forward platform migration failed.');
    }
    if ($phase !== 'migrations') {
        RequiredSeeds::run($app, $argv[1]);
    }
    fwrite(STDOUT, "PASS: forward platform data preparation completed.\n");
} catch (Throwable) {
    fwrite(STDERR, "Forward platform data preparation failed; preserve operation evidence and inspect migration/seed state.\n");
    exit(1);
}
