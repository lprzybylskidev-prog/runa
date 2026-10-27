<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Runa\Modules\Auth\Actions\ChangeIdentity;
use Runa\Modules\Auth\Contracts\IdentityConflict;
use Runa\Modules\Auth\Contracts\LifecycleChange;
use Runa\Support\TestDatabaseGuard;

require realpath(__DIR__.'/..').'/bootstrap.php';
$app = require 'bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$app->make(TestDatabaseGuard::class)->assertSafe();
fwrite(STDOUT, 'ready'.PHP_EOL);
fgets(STDIN); // Parent releases both real workers while retaining the invariant lock.
try {
    $app->make(ChangeIdentity::class)->change($argv[1], (int) $argv[2], new LifecycleChange('deactivate'));
    fwrite(STDOUT, 'changed'.PHP_EOL);
} catch (IdentityConflict $error) {
    fwrite(STDOUT, $error->getMessage().PHP_EOL);
}
if (DB::connection()->transactionLevel() !== 0) {
    throw new RuntimeException('Authority worker leaked its business transaction.');
}
