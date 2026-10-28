<?php

namespace Runa\Providers;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\Config\Repository;
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
use Runa\Configuration\ConfigurationValidator;
use Runa\Console\Commands\CheckApplicationManifest;
use Runa\Console\Commands\CheckConfigurationTemplates;
use Runa\Console\Commands\CheckModules;
use Runa\Console\Commands\CheckRuntimeHealth;
use Runa\Console\Commands\Database\GuardedFreshCommand;
use Runa\Console\Commands\Database\GuardedRefreshCommand;
use Runa\Console\Commands\Database\GuardedResetCommand;
use Runa\Console\Commands\Database\GuardedRollbackCommand;
use Runa\Console\Commands\Database\GuardedWipeCommand;
use Runa\Console\Commands\Database\ScopedMigrateCommand;
use Runa\Console\Commands\Database\ScopedSeedCommand;
use Runa\Console\Commands\Database\ScopedStatusCommand;
use Runa\Console\Commands\DescribeModuleManagement;
use Runa\Console\Commands\ManageAuthOperator;
use Runa\Console\Commands\ManageModuleSettings;
use Runa\Console\Commands\PulseScheduler;
use Runa\Console\Commands\RecoverModuleHealth;
use Runa\Console\Commands\SeedProject;
use Runa\Console\Commands\SynchronizeModules;
use Runa\Console\Commands\TestMail;
use Runa\Console\Commands\ValidateConfiguration;
use Runa\Database\DatabaseScope;
use Runa\Database\IdentityMigrationRepository;
use Runa\Logging\ConsoleLogContext;
use Runa\Logging\QueueLogContext;
use Runa\Mail\MailBranding;
use Runa\ModuleFramework\Console\ModuleConsole;
use Runa\ModuleFramework\Work\ModuleQueueGuard;
use Runa\Runtime\RuntimeHeartbeat;
use Runa\Support\Logging\LogContext;
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
        $this->app->singleton(LogContext::class);
        $this->app->singleton(ConsoleLogContext::class);
        $this->app->singleton(QueueLogContext::class);
        $this->app->register(ModuleFrameworkServiceProvider::class);
        $this->app->register(CoreIntegrationServiceProvider::class);
        $this->app->register(DevelopmentServiceProvider::class);
        foreach ([MigrateCommand::class => ScopedMigrateCommand::class, StatusCommand::class => ScopedStatusCommand::class, SeedCommand::class => ScopedSeedCommand::class] as $native => $scoped) {
            $this->app->extend($native, fn ($command, Application $app) => $app->make($scoped));
        }
        $this->commands([
            CheckApplicationManifest::class,
            SynchronizeModules::class,
            CheckModules::class,
            RecoverModuleHealth::class,
            ManageModuleSettings::class,
            new ManageAuthOperator('initialize'),
            new ManageAuthOperator('recover'),
            new ManageAuthOperator('restore-policy'),
            DescribeModuleManagement::class,
            CheckRuntimeHealth::class,
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
