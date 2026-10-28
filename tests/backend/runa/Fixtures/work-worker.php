<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Queue\Factory;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Support\Facades\DB;
use Runa\Integration\Core\DenyProtectedOperations;
use Runa\ModuleFramework\Api\ModuleDeclarations;
use Runa\ModuleFramework\Api\OperationGuard;
use Runa\ModuleFramework\Api\OperationScope;
use Runa\ModuleFramework\HealthDeadline;
use Runa\ModuleFramework\HealthPolicies;
use Runa\ModuleFramework\LifecyclePolicies;
use Runa\ModuleFramework\ModuleAutoloader;
use Runa\ModuleFramework\ModuleAvailability;
use Runa\ModuleFramework\ModuleDiscovery;
use Runa\ModuleFramework\ModuleHealth;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\ModuleFramework\Work\WorkDefinitions;
use Runa\Runtime\MaintenanceState;
use Runa\Support\Modules\Work\ModuleWork;
use Runa\Support\TestDatabaseGuard;
use Tests\Fixtures\WorkCalls;

require realpath(__DIR__.'/..').'/bootstrap.php';
$app = require 'bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$app->make(TestDatabaseGuard::class)->assertSafe();
$db = DB::connection();
$registry = new ModuleRegistry($db, new ModuleDiscovery($argv[1]));
$loader = new ModuleAutoloader($argv[1], $registry, true);
$lifecycle = new LifecyclePolicies($argv[1], $app, $loader, true);
$health = new ModuleHealth($db, $registry, new HealthPolicies($argv[1], $app, $loader, true), new HealthDeadline);
$availability = new ModuleAvailability($registry, $health, $lifecycle, '0.4.0');
$scope = new OperationScope;
$guard = new OperationGuard($registry, $availability, new MaintenanceState($app, $db, $registry, $availability), new DenyProtectedOperations, $app, $scope);
$definitions = new WorkDefinitions($registry, new ModuleDeclarations($argv[1], $app, $loader, true));
$app->instance(ModuleWork::class, new ModuleWork($db, $registry, $definitions, $guard, $scope, $app->make(Encrypter::class), $app->make(Factory::class)));
if (($argv[3] ?? '') === 'crash') {
    WorkCalls::$handle = static function (): never {
        exit(23);
    };
}
$app->make('queue.worker')->runNextJob('database', $argv[2], new WorkerOptions(sleep: 0, force: true));
