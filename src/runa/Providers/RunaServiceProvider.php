<?php

namespace Runa\Providers;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Foundation\Application;
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
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\WorkerIdle;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Runa\Console\Commands\CheckApplicationManifest;
use Runa\Console\Commands\CheckConfigurationTemplates;
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
use Runa\Console\Commands\Generators\ApplicationConfigMakeCommand;
use Runa\Console\Commands\Generators\ApplicationTestMakeCommand;
use Runa\Console\Commands\PulseScheduler;
use Runa\Console\Commands\RecoverModuleHealth;
use Runa\Console\Commands\SeedProject;
use Runa\Console\Commands\SynchronizeModules;
use Runa\Console\Commands\TestMail;
use Runa\Console\Commands\ValidateConfiguration;
use Runa\Database\DatabaseScope;
use Runa\Database\IdentityMigrationRepository;
use Runa\Support\ConfigurationValidator;
use Runa\Support\MailBranding;
use Runa\Support\MaintenanceState;
use Runa\Support\Modules\HealthDeadline;
use Runa\Support\Modules\HealthPolicies;
use Runa\Support\Modules\LifecyclePolicies;
use Runa\Support\Modules\ModuleAutoloader;
use Runa\Support\Modules\ModuleAvailability;
use Runa\Support\Modules\ModuleDiscovery;
use Runa\Support\Modules\ModuleHealth;
use Runa\Support\Modules\ModuleRegistry;
use Runa\Support\Modules\ModuleTransitions;
use Runa\Support\PlatformManifest;
use Runa\Support\RuntimeHeartbeat;
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
        $this->app->singletonIf(DatabaseScope::class, fn () => new DatabaseScope(base_path()));
        $this->app->singleton(ModuleDiscovery::class, fn () => new ModuleDiscovery(base_path(), $this->app->environment(['local', 'testing'])));
        $this->app->singleton(ModuleRegistry::class, fn () => new ModuleRegistry($this->app->make(DatabaseManager::class)->connection(), $this->app->make(ModuleDiscovery::class)));
        $this->app->singleton(ModuleAutoloader::class, fn () => new ModuleAutoloader(base_path(), $this->app->make(ModuleRegistry::class), $this->app->environment(['local', 'testing'])));
        $this->app->singleton(LifecyclePolicies::class, fn () => new LifecyclePolicies(base_path(), $this->app, $this->app->make(ModuleAutoloader::class), $this->app->environment(['local', 'testing'])));
        $this->app->singleton(ModuleTransitions::class, fn () => new ModuleTransitions($this->app->make(DatabaseManager::class)->connection(), $this->app->make(ModuleRegistry::class), $this->app->make(LifecyclePolicies::class), $this->app->make(Encrypter::class), PlatformManifest::load(base_path('runa/platform.json'))->version, $this->app->make(ModuleHealth::class)));
        $this->app->singleton(HealthPolicies::class, fn () => new HealthPolicies(base_path(), $this->app, $this->app->make(ModuleAutoloader::class), $this->app->environment(['local', 'testing'])));
        $this->app->singleton(ModuleHealth::class, fn () => new ModuleHealth($this->app->make(DatabaseManager::class)->connection(), $this->app->make(ModuleRegistry::class), $this->app->make(HealthPolicies::class), new HealthDeadline));
        $this->app->singleton(ModuleAvailability::class, fn () => new ModuleAvailability($this->app->make(ModuleRegistry::class), $this->app->make(ModuleHealth::class), $this->app->make(LifecyclePolicies::class), PlatformManifest::load(base_path('runa/platform.json'))->version));
        $this->app->singleton(MaintenanceState::class, fn () => new MaintenanceState($this->app, $this->app->make(DatabaseManager::class)->connection(), $this->app->make(ModuleRegistry::class), $this->app->make(ModuleAvailability::class)));
        ModuleAutoloader::register();
        foreach ([MigrateCommand::class => ScopedMigrateCommand::class, StatusCommand::class => ScopedStatusCommand::class, SeedCommand::class => ScopedSeedCommand::class] as $native => $scoped) {
            $this->app->extend($native, fn ($command, Application $app) => $app->make($scoped));
        }
        $this->commands([
            CheckApplicationManifest::class,
            SynchronizeModules::class,
            RecoverModuleHealth::class,
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

        Event::listen(MessageSending::class, [MailBranding::class, 'attachLogo']);
        Event::listen(WorkerIdle::class, fn () => $heartbeat->record('queue'));
        Event::listen(JobProcessed::class, fn () => $heartbeat->record('queue'));
        if (! $this->app->runningInConsole() && ($errors = $validator->errors()) !== []) {
            throw new RuntimeException('Invalid application configuration: '.implode(' ', $errors));
        }
    }
}
