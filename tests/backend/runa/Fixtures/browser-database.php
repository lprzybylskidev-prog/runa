<?php

// Private browser preparation, called while the supported test runner holds its suite lock.
use Illuminate\Contracts\Console\Kernel;
use Runa\ModuleFramework\Updates\ModuleUpdates;
use Runa\Testing\Database\TestDatabaseGuard;

require __DIR__.'/../bootstrap.php';
$app = require __DIR__.'/../../../../src/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$app->make(TestDatabaseGuard::class)->assertSafe();
$release = $argv[1] ?? '';
if (! preg_match('/\A[a-f0-9]{40}\z/D', $release)) {
    throw new RuntimeException('Browser preparation requires the actual checkout commit.');
}
$app->make(ModuleUpdates::class)->apply('runa', $release, static function (): void {});
fwrite(STDOUT, "PASS: guarded browser database Core modules prepared forward; existing application data preserved.\n");
