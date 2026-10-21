<?php

namespace Tests\Fixtures;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;

final class DatabaseOwnership
{
    public readonly string $root;

    public function __construct()
    {
        $this->root = sys_get_temp_dir().'/runa-db-ownership-'.bin2hex(random_bytes(6));
        foreach (['app/database/migrations', 'app/database/seeders/Production', 'runa/database/migrations', 'runa/database/seeders'] as $directory) {
            mkdir($this->root.'/'.$directory, 0775, true);
        }
        foreach (glob(base_path('runa/database/migrations/*.php')) ?: [] as $file) {
            copy($file, $this->root.'/runa/database/migrations/'.basename($file));
        }
        file_put_contents($this->root.'/app/database/migrations/2099_01_01_000001_create_records.php', <<<'CODE'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        DB::statement('CREATE SCHEMA IF NOT EXISTS app_9c');
        Schema::create('app_9c.records', function (Blueprint $table): void {
            $table->bigIncrements('id')->generatedAs()->always();
            $table->text('label');
        });
    }
    public function down(): void { Schema::dropIfExists('app_9c.records'); }
};
CODE);
        copy(base_path('app/database/seeders/DatabaseSeeder.php'), $this->root.'/app/database/seeders/DatabaseSeeder.php');
        file_put_contents($this->root.'/app/database/seeders/ManagedFixtureSeed.php', <<<'CODE'
<?php
namespace Database\Seeders;
use Runa\Support\Seeding\ManagedSeeder;
use Runa\Support\Seeding\SeedCategory;
use Runa\Support\Seeding\SeedContext;
use Illuminate\Support\Facades\DB;
final class ManagedFixtureSeed extends ManagedSeeder {
    public static function category(): SeedCategory { return SeedCategory::Production; }
    protected function seed(SeedContext $context): void { DB::table('app_9c.records')->insert(['label' => 'application-seed']); }
}
CODE);
        file_put_contents($this->root.'/app/database/seeders/Production/LooseSeeder.php', <<<'CODE'
<?php
namespace Database\Seeders\Production;
final class LooseSeeder extends \Illuminate\Database\Seeder {
    public function run(): void { throw new \RuntimeException('A loose application seeder must never run automatically.'); }
}
CODE);
        file_put_contents($this->root.'/runa/database/seeders/DatabaseSeeder.php', <<<'CODE'
<?php
namespace Runa\Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
final class DatabaseSeeder extends Seeder {
    public function run(): void {
        DB::table('shared.jobs')->insert(['queue' => '9c', 'payload' => 'platform-seed', 'attempts' => 0, 'available_at' => 0, 'created_at' => 0]);
        if (getenv('TEST_9C_FAIL_SEED') === '1') { throw new \RuntimeException('Synthetic seed failure.'); }
    }
}
CODE);
        $bootstrap = var_export(base_path('bootstrap/app.php'), true);
        $autoload = var_export(base_path('vendor/autoload.php'), true);
        $fixture = var_export($this->root, true);
        file_put_contents($this->root.'/command.php', "<?php\nrequire $autoload;\n\$root = $fixture;\n".<<<'CODE'
require $root.'/app/database/seeders/DatabaseSeeder.php';
require $root.'/app/database/seeders/ManagedFixtureSeed.php';
require $root.'/runa/database/seeders/DatabaseSeeder.php';
$platform = $argv[1] === 'platform';
array_splice($argv, 1, 1);
CODE."\n\$app = require $bootstrap;\n".<<<'CODE'
$app->useAppPath($root.'/app');
$app->useDatabasePath($root.'/app/database');
$app->instance(Runa\Database\DatabaseScope::class, new Runa\Database\DatabaseScope($root, $platform));
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$app->make('config')->set('seeding.register', [Database\Seeders\ManagedFixtureSeed::class]);
exit($app->handleCommand(new Symfony\Component\Console\Input\ArgvInput($argv)));
CODE);
        $installer = var_export(dirname(base_path()).'/distribution/platform-database.php', true);
        $seeder = var_export($this->root.'/runa/database/seeders/DatabaseSeeder.php', true);
        file_put_contents($this->root.'/seed-install.php', "<?php\nrequire $autoload;\nrequire $seeder;\nrequire $installer;\n");
    }

    /** @param list<string> $arguments */
    public function command(bool $platform, array $arguments, bool $success = true): Process
    {
        $process = $this->execute([$this->root.'/command.php', $platform ? 'platform' : 'application', ...$arguments]);
        if ($success && ! $process->isSuccessful()) {
            throw new RuntimeException($process->getOutput().$process->getErrorOutput());
        }
        if (! $success && $process->isSuccessful()) {
            throw new RuntimeException('Unsafe scoped command unexpectedly succeeded.');
        }

        return $process;
    }

    public function installSeeds(string $release, bool $fail = false): Process
    {
        return $this->execute([$this->root.'/seed-install.php', 'install-seeds', $release], $fail ? ['TEST_9C_FAIL_SEED' => '1'] : []);
    }

    /** @param list<string> $arguments
     * @param  array<string, string>  $environment
     */
    private function execute(array $arguments, array $environment = []): Process
    {
        $identity = DB::selectOne('SELECT current_database() AS name, session_user AS role');
        if (! $identity instanceof \stdClass || $identity->role !== 'runa_test') {
            throw new RuntimeException('Ownership acceptance is restricted to the test database role.');
        }
        $process = new Process([PHP_BINARY, ...$arguments], base_path(), [
            'APP_ENV' => 'testing', 'DB_DATABASE' => $identity->name, 'TEST_TOKEN' => false,
            'LARAVEL_STORAGE_PATH' => storage_path(), ...$environment,
        ]);
        $process->setTimeout(null);
        $process->run();

        return $process;
    }

    public function remove(): void
    {
        File::deleteDirectory($this->root);
    }
}
