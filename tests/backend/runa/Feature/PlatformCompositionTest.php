<?php

namespace Tests\Architecture;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Application;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use Runa\Contracts\Modules\OperationAuthorizer;
use Runa\Contracts\Modules\WorkHandler;
use Runa\ModuleFramework\Api\OperationScope;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\ModuleFramework\Updates\ModuleUpdates;
use Runa\ModuleFramework\Work\ModuleQueueGuard;
use Runa\Providers\CoreIntegrationServiceProvider;
use Runa\Providers\DevelopmentServiceProvider;
use Runa\Providers\ModuleFrameworkServiceProvider;
use Runa\Providers\RunaServiceProvider;
use Runa\Support\Modules\Work\ModuleJob;
use Runa\Support\Modules\Work\WorkContext;
use Symfony\Component\Process\Process;

final class PlatformCompositionTest extends TestCase
{
    public function test_relocated_platform_classes_and_public_signatures_autoload(): void
    {
        $root = dirname(__DIR__, 4);
        $map = json_decode(file_get_contents($root.'/docs/runa/maintainer/architecture/api-boundary/source-map.json'), true, flags: JSON_THROW_ON_ERROR);
        foreach ($map['files'] as $file) {
            if ($file['point'] !== 13 && ! str_starts_with($file['source'], 'src/runa/Support/')) {
                continue;
            }
            $class = $file['target_symbol'] ?? null;
            if ($class === null || str_starts_with($class, 'Runa\\Integration\\Core\\') || ($file['point'] !== 13 && $file['target'] !== $file['source'])) {
                continue;
            }
            $reflection = new ReflectionClass($class);
            self::assertSame($root.'/'.$file['target'], $reflection->getFileName());
            foreach ($reflection->getMethods() as $method) {
                $types = [$method->getReturnType()];
                foreach ($method->getParameters() as $parameter) {
                    $types[] = $parameter->getType();
                }
                foreach ($types as $type) {
                    if ($type instanceof ReflectionNamedType && str_starts_with($type->getName(), 'Runa\\') && ! str_starts_with($type->getName(), 'Runa\\Modules\\')) {
                        self::assertTrue(class_exists($type->getName()) || interface_exists($type->getName()) || enum_exists($type->getName()), $class.'::'.$method->getName().' requires '.$type->getName());
                    }
                }
            }
        }
        self::assertSame(WorkContext::class, (new ReflectionClass(WorkHandler::class))->getMethod('handle')->getParameters()[0]->getType()->getName());
    }

    public function test_provider_registration_stays_lazy_and_keeps_explicit_bindings(): void
    {
        $previous = Container::getInstance();
        // ServiceProvider::commands adds process-wide Artisan callbacks.
        $bootstrappers = new ReflectionProperty(\Illuminate\Console\Application::class, 'bootstrappers');
        $previousBootstrappers = $bootstrappers->getValue();
        $app = new Application(dirname(__DIR__, 4).'/src');
        $app->useAppPath($app->basePath('app'));
        $app->instance('env', 'testing');
        $app->instance('config', new Repository);
        $database = $this->createMock(DatabaseManager::class);
        $database->expects(self::never())->method('connection');
        $app->instance(DatabaseManager::class, $database);
        try {
            $app->register(RunaServiceProvider::class);
            foreach ([ModuleFrameworkServiceProvider::class, CoreIntegrationServiceProvider::class, DevelopmentServiceProvider::class] as $provider) {
                self::assertNotNull($app->getProvider($provider));
            }
            foreach ([ModuleRegistry::class, ModuleUpdates::class, OperationAuthorizer::class] as $contract) {
                self::assertTrue($app->bound($contract));
            }
            self::assertSame($app->make(OperationScope::class), $app->make(OperationScope::class));
            self::assertFalse($app->resolved(ModuleRegistry::class));
        } finally {
            $bootstrappers->setValue(null, $previousBootstrappers);
            Container::setInstance($previous);
        }
    }

    public function test_historical_queue_envelope_retains_identity_and_admission_metadata(): void
    {
        $job = unserialize(file_get_contents(__DIR__.'/../Fixtures/queue/module-job-0.5.0.serialized'), ['allowed_classes' => [ModuleJob::class]]);
        self::assertInstanceOf(ModuleJob::class, $job);
        self::assertSame('019a2b34-5678-7000-8000-123456789abc', $job->workId);
        self::assertSame(55, $job->timeout);
        self::assertSame(['runa_work' => $job->workId], ModuleQueueGuard::payload('database', null, ['data' => ['commandName' => $job]]));
        self::assertSame(ModuleJob::class, $job::class);
    }

    public function test_isolated_target_planner_finds_the_compatible_core_forward_edges(): void
    {
        $root = dirname(__DIR__, 4);
        $modules = [];
        foreach (['audit', 'auth', 'permissions'] as $code) {
            $manifest = json_decode(file_get_contents($root.'/src/runa/modules/'.$code.'/module.json'), true, flags: JSON_THROW_ON_ERROR);
            $modules['runa.'.$code] = ['installed' => '1.0.1', 'version' => '1.0.1', 'registered' => true, 'required' => $manifest['dependencies']['required']];
        }
        $process = new Process([PHP_BINARY, $root.'/distribution/module-plan.php', $root.'/src', $root.'/src'], sys_get_temp_dir());
        $process->setInput(json_encode(['platform' => trim(file_get_contents($root.'/VERSION')), 'modules' => $modules, 'module_steps' => []], JSON_THROW_ON_ERROR));
        $process->mustRun();
        $plan = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        self::assertCount(3, $plan);
        foreach ($plan as $module) {
            $auth = $module['module'] === 'runa.auth';
            self::assertSame($auth ? '1.1.1' : '1.0.2', $module['to']);
            self::assertSame($auth ? ['platform_composition', 'execution_layout', 'frontend_contracts', 'account_orchestration'] : ['platform_composition'], $module['pending_upgrades']);
            self::assertSame('1.0.1', $module['from']);
        }
    }
}
