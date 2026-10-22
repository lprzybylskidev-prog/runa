<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Queue\Factory;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Support\Facades\DB;
use Runa\Support\MaintenanceState;
use Runa\Support\Modules\Api\DenyProtectedOperations;
use Runa\Support\Modules\Api\ModuleDeclarations;
use Runa\Support\Modules\Api\OperationGuard;
use Runa\Support\Modules\Api\OperationScope;
use Runa\Support\Modules\HealthDeadline;
use Runa\Support\Modules\HealthPolicies;
use Runa\Support\Modules\LifecyclePolicies;
use Runa\Support\Modules\ModuleAutoloader;
use Runa\Support\Modules\ModuleAvailability;
use Runa\Support\Modules\ModuleDiscovery;
use Runa\Support\Modules\ModuleHealth;
use Runa\Support\Modules\ModuleRegistry;
use Runa\Support\Modules\Work\ModuleWork;
use Runa\Support\Modules\Work\WorkDefinitions;
use Runa\Support\TestDatabaseGuard;
use Tests\Fixtures\WorkCalls;

require dirname(__DIR__).'/bootstrap.php';
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
