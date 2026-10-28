<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Runa\Modules\Auth\Actions\OperatorAccounts;
use Runa\Modules\Auth\Contracts\NewIdentity;
use Runa\Testing\Database\TestDatabaseGuard;

require realpath(__DIR__.'/..').'/bootstrap.php';
$app = require 'bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$app->make(TestDatabaseGuard::class)->assertSafe();
fwrite(STDOUT, 'ready'.PHP_EOL);
fgets(STDIN);
try {
    $app->make(OperatorAccounts::class)->initialize(new NewIdentity($argv[1], name: 'Concurrent initial administrator'));
    fwrite(STDOUT, 'initialized'.PHP_EOL); // Secret return value never leaves this process.
} catch (RuntimeException) {
    fwrite(STDOUT, 'refused'.PHP_EOL);
}
if (DB::connection()->transactionLevel() !== 0) {
    throw new RuntimeException('Initialization worker leaked its transaction.');
}
