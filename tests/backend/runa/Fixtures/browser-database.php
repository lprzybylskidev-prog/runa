<?php

// Private browser preparation, called while the supported test runner holds its suite lock.
use Illuminate\Contracts\Console\Kernel;
use Runa\ModuleFramework\Updates\ModuleUpdates;
use Runa\Testing\Backend\TestRun;
use Runa\Testing\Database\TestDatabaseGuard;

require __DIR__.'/../bootstrap.php';
try {
    // The browser runner inherits the already booted development Artisan environment.
    // Select the same isolated identity as the endpoint before any provider captures it.
    foreach (TestRun::testingEnvironment(dirname(__DIR__, 4)) as $key => $value) {
        unset($_ENV[$key], $_SERVER[$key]);
        putenv($value === false ? $key : $key.'='.$value);
    }
    $app = require __DIR__.'/../../../../src/bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();
    $app->make(TestDatabaseGuard::class)->assertSafe();
    $release = $argv[1] ?? '';
    if (! preg_match('/\A[a-f0-9]{40}\z/D', $release)) {
        throw new RuntimeException('Browser preparation requires the actual checkout commit.');
    }
    $app->make(ModuleUpdates::class)->apply('runa', $release, static function (): void {});
    fwrite(STDOUT, "PASS: guarded browser database Core modules prepared forward; existing application data preserved.\n");
} catch (Throwable) {
    fwrite(STDERR, "Browser database preparation failed; preserve evidence and inspect the isolated test database.\n");
    exit(1);
}
