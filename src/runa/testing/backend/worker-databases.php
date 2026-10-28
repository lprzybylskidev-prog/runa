<?php

// Fixed-purpose child process; the caller must hold .runtime/tests/suite.lock.
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Runa\Testing\Database\TestDatabaseGuard;

$failure = 'Worker cleanup requires the isolated testing environment.';
try {
    if (getenv('APP_ENV') !== 'testing') {
        throw new RuntimeException('Worker cleanup requires the isolated testing environment.');
    }

    require dirname(__DIR__, 3).'/vendor/autoload.php';
    $app = require dirname(__DIR__, 3).'/bootstrap/app.php';
    $failure = 'Worker cleanup could not verify the isolated testing database identity.';
    $app->make(Kernel::class)->bootstrap();
    $app->make(TestDatabaseGuard::class)->assertSafe();
    if (DB::connection()->getDatabaseName() !== 'runa_test') {
        throw new RuntimeException('Worker cleanup must connect to the isolated base test database.');
    }
    $failure = 'Worker cleanup could not inspect test database ownership/activity.';
    $workers = DB::select(<<<'SQL'
        SELECT d.datname, pg_get_userbyid(d.datdba) AS owner,
               (SELECT count(*) FROM pg_stat_activity a WHERE a.datid = d.oid) AS connections
        FROM pg_database d
        WHERE d.datname ~ '^runa_test_test_[1-9][0-9]{0,4}$'
        ORDER BY d.datname
        SQL);
    fwrite(STDOUT, 'Scoped worker database cleanup: '.json_encode(array_column($workers, 'datname'), JSON_THROW_ON_ERROR).PHP_EOL);
    // Refuse the complete cleanup before deleting anything when another user of a
    // worker is visible. DROP DATABASE also refuses a connection racing this query.
    foreach ($workers as $worker) {
        if ($worker->owner !== 'runa_test') {
            $failure = 'Foreign-owned test worker database preserved: '.$worker->datname;
            throw new RuntimeException($failure);
        }
        if ((int) $worker->connections !== 0) {
            $failure = 'Active test worker database preserved: '.$worker->datname;
            throw new RuntimeException($failure);
        }
    }
    foreach ($workers as $worker) {
        if (! preg_match('/\Aruna_test_test_[1-9][0-9]{0,4}\z/D', $worker->datname)) {
            throw new RuntimeException('Unexpected worker database identity.');
        }
        $failure = 'Could not remove test worker database: '.$worker->datname;
        DB::statement('DROP DATABASE IF EXISTS "'.$worker->datname.'"');
    }

} catch (Throwable) {
    // Laravel installs an exception handler during bootstrap. Explicitly fail the
    // process rather than letting that renderer return a successful exit code.
    fwrite(STDERR, $failure.PHP_EOL);
    exit(1);
}
