<?php

namespace Runa\Providers;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Queue\Factory;
use Illuminate\Database\Console\Migrations\FreshCommand;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\Console\Migrations\RefreshCommand;
use Illuminate\Database\Console\Migrations\ResetCommand;
use Illuminate\Database\Console\Migrations\RollbackCommand;
use Illuminate\Database\Console\Migrations\StatusCommand;
use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Database\Console\WipeCommand;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Foundation\Console\ConfigMakeCommand;
use Illuminate\Foundation\Console\TestMakeCommand;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\WorkerIdle;
use Illuminate\Queue\Events\WorkerStarting;
use Illuminate\Queue\Events\WorkerStopping;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use NunoMaduro\Collision\Adapters\Laravel\Commands\TestCommand;
use Psr\Log\LoggerInterface;
use Runa\Console\Commands\CheckApplicationManifest;
use Runa\Console\Commands\CheckConfigurationTemplates;
use Runa\Console\Commands\CheckModules;
use Runa\Console\Commands\CheckRuntimeHealth;
use Runa\Console\Commands\CheckTestDatabase;
use Runa\Console\Commands\Database\GuardedFreshCommand;
use Runa\Console\Commands\Database\GuardedRefreshCommand;
use Runa\Console\Commands\Database\GuardedResetCommand;
use Runa\Console\Commands\Database\GuardedRollbackCommand;
use Runa\Console\Commands\Database\GuardedWipeCommand;
use Runa\Console\Commands\Database\ScopedMigrateCommand;
use Runa\Console\Commands\Database\ScopedSeedCommand;
use Runa\Console\Commands\Database\ScopedStatusCommand;
use Runa\Console\Commands\DescribeModuleManagement;
use Runa\Console\Commands\Generators\ApplicationConfigMakeCommand;
use Runa\Console\Commands\Generators\ApplicationTestMakeCommand;
use Runa\Console\Commands\MakeModule;
use Runa\Console\Commands\ManageModuleSettings;
use Runa\Console\Commands\PulseScheduler;
use Runa\Console\Commands\RecoverModuleHealth;
use Runa\Console\Commands\SeedProject;
use Runa\Console\Commands\SynchronizeModules;
use Runa\Console\Commands\TestMail;
use Runa\Console\Commands\ValidateConfiguration;
use Runa\Contracts\Modules\OperationAuthorizer;
use Runa\Contracts\Modules\SettingsObserver;
use Runa\Database\DatabaseScope;
use Runa\Database\IdentityMigrationRepository;
use Runa\Support\ConfigurationValidator;
use Runa\Support\Logging\ConsoleLogContext;
use Runa\Support\Logging\LogContext;
use Runa\Support\Logging\QueueLogContext;
use Runa\Support\MailBranding;
use Runa\Support\MaintenanceState;
use Runa\Support\Modules\Api\DenyProtectedOperations;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\ModuleDeclarations;
use Runa\Support\Modules\Api\ModuleHttp;
use Runa\Support\Modules\Api\OperationGuard;
use Runa\Support\Modules\Api\OperationScope;
use Runa\Support\Modules\Console\ModuleConsole;
use Runa\Support\Modules\Data\ModuleDataPaths;
use Runa\Support\Modules\Events\EventDefinitions;
use Runa\Support\Modules\Events\EventSubscriptions;
use Runa\Support\Modules\Events\ModuleEvents;
use Runa\Support\Modules\Events\ModuleIntegrations;
use Runa\Support\Modules\Frontend\ModuleFrontend;
use Runa\Support\Modules\Frontend\ModuleFrontendRoutes;
use Runa\Support\Modules\HealthDeadline;
use Runa\Support\Modules\HealthPolicies;
use Runa\Support\Modules\LifecyclePolicies;
use Runa\Support\Modules\Management\ManagementRenderers;
use Runa\Support\Modules\Management\ModuleManagement;
use Runa\Support\Modules\ModuleAutoloader;
use Runa\Support\Modules\ModuleAvailability;
use Runa\Support\Modules\ModuleDiscovery;
use Runa\Support\Modules\ModuleHealth;
use Runa\Support\Modules\ModuleRegistry;
use Runa\Support\Modules\ModuleTransitions;
use Runa\Support\Modules\Settings\IgnoreSettingsChanges;
use Runa\Support\Modules\Settings\ModuleSettings;
use Runa\Support\Modules\Settings\SettingsDefinitions;
use Runa\Support\Modules\Settings\SettingTypes;
use Runa\Support\Modules\Storage\ModuleStorage;
use Runa\Support\Modules\Updates\ModuleUpdates;
use Runa\Support\Modules\Work\ModuleQueueGuard;
use Runa\Support\Modules\Work\ModuleWork;
use Runa\Support\Modules\Work\WorkDefinitions;
use Runa\Support\PlatformManifest;
use Runa\Support\RuntimeHeartbeat;
use Runa\Testing\ApplicationTestCommand;
use RuntimeException;

final class RunaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (! $this->app->configurationIsCached()) {
            foreach (glob($this->app->path('config').'/*.php') ?: [] as $file) {
                $overrides = require $file;
                if (! is_array($overrides)) {
                    throw new RuntimeException('Application configuration must return an array.');
                }
                $key = basename($file, '.php');
                if ($key === 'branding' && array_intersect(array_keys($overrides), ['application_id', 'application_name', 'application_version', 'platform_version', 'logo']) !== []) {
                    throw new RuntimeException('Application identity belongs in app/application.json, not branding configuration.');
                }
                $config = $this->app->make(Repository::class);
                $defaults = $config->get($key, []);
                if (! is_array($defaults)) {
                    throw new RuntimeException('Application configuration cannot replace a non-array namespace.');
                }
                $config->set($key, array_replace_recursive($defaults, $overrides));
            }
        }
        $this->app->extend(ConfigMakeCommand::class,
            fn ($command, Application $app) => $app->make(ApplicationConfigMakeCommand::class));
        $this->app->extend(TestMakeCommand::class,
            fn ($command, Application $app) => $app->make(ApplicationTestMakeCommand::class));
        if ($this->app->environment(['local', 'testing']) && class_exists(TestCommand::class)) {
            $this->app->extend(TestCommand::class,
                fn ($command, Application $app) => $app->make(ApplicationTestCommand::class));
        }
        $this->app->singletonIf(DatabaseScope::class, fn () => new DatabaseScope(base_path()));
        $this->app->singleton(ModuleUpdates::class, fn () => new ModuleUpdates($this->app->make(DatabaseManager::class)->connection(), $this->app->make(ModuleRegistry::class), base_path(), PlatformManifest::load(base_path('runa/platform.json'))->version, $this->app->environment(['local', 'testing'])));
        $this->app->singleton(ModuleDiscovery::class, fn () => new ModuleDiscovery(base_path(), $this->app->environment(['local', 'testing'])));
        $this->app->singleton(ModuleDataPaths::class, fn () => new ModuleDataPaths(base_path(), $this->app->environment(['local', 'testing'])));
        $this->app->singleton(ModuleStorage::class);
        $this->app->singleton(ModuleRegistry::class, fn () => new ModuleRegistry($this->app->make(DatabaseManager::class)->connection(), $this->app->make(ModuleDiscovery::class)));
        $this->app->singleton(ModuleAutoloader::class, fn () => new ModuleAutoloader(base_path(), $this->app->make(ModuleRegistry::class), $this->app->environment(['local', 'testing'])));
        $this->app->singleton(LifecyclePolicies::class, fn () => new LifecyclePolicies(base_path(), $this->app, $this->app->make(ModuleAutoloader::class), $this->app->environment(['local', 'testing'])));
        $this->app->singleton(ModuleTransitions::class, fn () => new ModuleTransitions($this->app->make(DatabaseManager::class)->connection(), $this->app->make(ModuleRegistry::class), $this->app->make(LifecyclePolicies::class), $this->app->make(Encrypter::class), PlatformManifest::load(base_path('runa/platform.json'))->version, $this->app->make(ModuleHealth::class)));
        $this->app->singleton(HealthPolicies::class, fn () => new HealthPolicies(base_path(), $this->app, $this->app->make(ModuleAutoloader::class), $this->app->environment(['local', 'testing'])));
        $this->app->singleton(ModuleHealth::class, fn () => new ModuleHealth($this->app->make(DatabaseManager::class)->connection(), $this->app->make(ModuleRegistry::class), $this->app->make(HealthPolicies::class), new HealthDeadline));
        $this->app->singleton(ModuleAvailability::class, fn () => new ModuleAvailability($this->app->make(ModuleRegistry::class), $this->app->make(ModuleHealth::class), $this->app->make(LifecyclePolicies::class), PlatformManifest::load(base_path('runa/platform.json'))->version));
        $this->app->singleton(MaintenanceState::class, fn () => new MaintenanceState($this->app, $this->app->make(DatabaseManager::class)->connection(), $this->app->make(ModuleRegistry::class), $this->app->make(ModuleAvailability::class)));
        $this->app->singleton(LogContext::class);
        $this->app->singleton(ConsoleLogContext::class);
        $this->app->singleton(QueueLogContext::class);
        $this->app->singleton(OperationScope::class);
        $this->app->bindIf(OperationAuthorizer::class, DenyProtectedOperations::class);
        $this->app->singleton(ModuleDeclarations::class, fn () => new ModuleDeclarations(base_path(), $this->app, $this->app->make(ModuleAutoloader::class), $this->app->environment(['local', 'testing'])));
        $this->app->singleton(OperationGuard::class, fn () => new OperationGuard($this->app->make(ModuleRegistry::class), $this->app->make(ModuleAvailability::class), $this->app->make(MaintenanceState::class), $this->app->make(OperationAuthorizer::class), $this->app, $this->app->make(OperationScope::class)));
        $this->app->singleton(SettingTypes::class);
        $this->app->singleton(SettingsDefinitions::class);
        $this->app->singleton(ManagementRenderers::class);
        $this->app->singleton(ModuleManagement::class);
        $this->app->singleton(ModuleFrontend::class, fn () => new ModuleFrontend($this->app->make(DatabaseManager::class)->connection(), $this->app->make(ModuleRegistry::class), $this->app->make(ModuleDeclarations::class), $this->app->make(ModuleAvailability::class), $this->app->make(OperationAuthorizer::class)));
        $this->app->singleton(ModuleFrontendRoutes::class, fn () => new ModuleFrontendRoutes($this->app->make(DatabaseManager::class)->connection(), $this->app->make(ModuleRegistry::class), $this->app->make(ModuleFrontend::class), $this->app->make('router')));
        $this->app->bindIf(SettingsObserver::class, IgnoreSettingsChanges::class);
        $this->app->singleton(ModuleSettings::class, fn () => new ModuleSettings($this->app->make(DatabaseManager::class)->connection(), $this->app->make(ModuleRegistry::class), $this->app->make(SettingsDefinitions::class), $this->app->make(Encrypter::class), $this->app->make(SettingsObserver::class)));
        $this->app->singleton(ModuleApis::class);
        $this->app->singleton(WorkDefinitions::class);
        $this->app->singleton(EventDefinitions::class);
        $this->app->singleton(ModuleIntegrations::class, fn () => new ModuleIntegrations($this->app->make(DatabaseManager::class)->connection(), $this->app->make(ModuleRegistry::class), $this->app->make(ModuleDeclarations::class), $this->app->make(OperationGuard::class)));
        $this->app->singleton(EventSubscriptions::class);
        $this->app->singleton(ModuleEvents::class, fn () => new ModuleEvents($this->app->make(DatabaseManager::class)->connection(), $this->app->make(ModuleRegistry::class), $this->app->make(EventDefinitions::class), $this->app->make(EventSubscriptions::class), $this->app->make(ModuleDeclarations::class), $this->app->make(OperationGuard::class), $this->app->make(OperationScope::class), $this->app->make(ModuleWork::class), $this->app->make(LoggerInterface::class)));
        $this->app->singleton(ModuleWork::class, fn () => new ModuleWork($this->app->make(DatabaseManager::class)->connection(), $this->app->make(ModuleRegistry::class), $this->app->make(WorkDefinitions::class), $this->app->make(OperationGuard::class), $this->app->make(OperationScope::class), $this->app->make(Encrypter::class), $this->app->make(Factory::class), $this->app->make(EventSubscriptions::class)));
        $this->app->singleton(ModuleHttp::class, fn () => new ModuleHttp($this->app->make(ModuleRegistry::class), $this->app->make(ModuleDeclarations::class), $this->app->make('router'), $this->app->make(DatabaseManager::class)->connection()));
        $this->app->singleton(ModuleConsole::class, fn () => new ModuleConsole($this->app->make(ModuleRegistry::class), $this->app->make(ModuleDeclarations::class), $this->app->make(WorkDefinitions::class), $this->app->make(ModuleWork::class), $this->app->make(DatabaseManager::class)->connection(), $this->app->make(LogContext::class)));
        ModuleAutoloader::register();
        foreach ([MigrateCommand::class => ScopedMigrateCommand::class, StatusCommand::class => ScopedStatusCommand::class, SeedCommand::class => ScopedSeedCommand::class] as $native => $scoped) {
            $this->app->extend($native, fn ($command, Application $app) => $app->make($scoped));
        }
        $this->commands([
            CheckApplicationManifest::class,
            SynchronizeModules::class,
            MakeModule::class,
            CheckModules::class,
            RecoverModuleHealth::class,
            ManageModuleSettings::class,
            DescribeModuleManagement::class,
            CheckRuntimeHealth::class,
            CheckTestDatabase::class,
            PulseScheduler::class,
            new SeedProject,
            new SeedProject('production'),
            new SeedProject('development'),
            new SeedProject('occasional'),
            new SeedProject('graph'),
            CheckConfigurationTemplates::class,
            ValidateConfiguration::class,
            TestMail::class,
        ]);
        $this->app->extend(FreshCommand::class,
            fn ($command, Application $app) => $app->make(GuardedFreshCommand::class));
        $this->app->extend(RefreshCommand::class,
            fn ($command, Application $app) => $app->make(GuardedRefreshCommand::class));
        $this->app->extend(ResetCommand::class,
            fn ($command, Application $app) => $app->make(GuardedResetCommand::class));
        $this->app->extend(RollbackCommand::class,
            fn ($command, Application $app) => $app->make(GuardedRollbackCommand::class));
        $this->app->extend(WipeCommand::class,
            fn ($command, Application $app) => $app->make(GuardedWipeCommand::class));
        $this->app->extend('migration.repository', function (DatabaseMigrationRepository $repository, Application $app): IdentityMigrationRepository {
            return new IdentityMigrationRepository($app->make(DatabaseManager::class), $app->make(DatabaseScope::class)->repository());
        });
    }

    public function boot(ConfigurationValidator $validator, RuntimeHeartbeat $heartbeat): void
    {
        if ($this->app->runningInConsole() && $this->app->environment(['local', 'testing'])) {
            $commands = base_path('runa/management/commands.php');
            if (is_file($commands)) {
                $this->commands(require $commands);
            }
        }

        $this->loadViewsFrom([app_path('resources/views/runa-mail'), base_path('runa/resources/views/runa-mail')], 'runa-mail');
        $this->loadViewsFrom([app_path('resources/views/errors'), base_path('runa/resources/views/errors')], 'runa-errors');
        $this->loadTranslationsFrom(lang_path(), 'runa');
        $this->app->make('translator')->addNamespace('application', app_path('lang'));
        $this->loadTranslationsFrom(app_path('lang'));
        $this->loadJsonTranslationsFrom(app_path('lang'));

        Event::listen(CommandStarting::class, [ConsoleLogContext::class, 'starting']);
        Event::listen(CommandFinished::class, [ConsoleLogContext::class, 'finished']);
        Event::listen(MessageSending::class, [MailBranding::class, 'attachLogo']);
        \Illuminate\Console\Application::starting(function ($artisan): void {
            try {
                $modules = $this->app->make(ModuleConsole::class);
            } catch (\Throwable) {
                // Unconfigured/cold database must not mask native setup and safety diagnostics.
                return;
            }
            $modules->registerCommands($artisan);
        });
        Queue::createPayloadUsing(static fn () => app(QueueLogContext::class)->payload());
        Queue::createPayloadUsing(ModuleQueueGuard::payload(...));
        Event::listen(JobProcessing::class, [QueueLogContext::class, 'before']);
        Event::listen(JobProcessed::class, [QueueLogContext::class, 'after']);
        Event::listen(JobExceptionOccurred::class, [QueueLogContext::class, 'after']);
        Event::listen(JobFailed::class, [QueueLogContext::class, 'after']);
        Event::listen(JobProcessing::class, ModuleQueueGuard::before(...));
        Event::listen(WorkerStarting::class, function () use ($heartbeat): void {
            $this->app->instance('runa.queue_worker_active', true);
            $heartbeat->record('queue');
        });
        Event::listen(WorkerStopping::class, function (): void {
            $this->app->instance('runa.queue_worker_active', false);
        });
        Event::listen(WorkerIdle::class, fn () => $heartbeat->record('queue'));
        Event::listen(JobProcessed::class, fn () => $heartbeat->record('queue'));
        if (! $this->app->runningInConsole() && ($errors = $validator->errors()) !== []) {
            throw new RuntimeException('Invalid application configuration: '.implode(' ', $errors));
        }
    }
}
