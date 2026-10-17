<?php

namespace App\Providers;

use App\Console\Commands\Database\GuardedFreshCommand;
use App\Console\Commands\Database\GuardedRefreshCommand;
use App\Console\Commands\Database\GuardedResetCommand;
use App\Console\Commands\Database\GuardedRollbackCommand;
use App\Console\Commands\Database\GuardedWipeCommand;
use App\Database\IdentityMigrationRepository;
use App\Support\ConfigurationValidator;
use App\Support\RuntimeHeartbeat;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Console\Migrations\FreshCommand;
use Illuminate\Database\Console\Migrations\RefreshCommand;
use Illuminate\Database\Console\Migrations\ResetCommand;
use Illuminate\Database\Console\Migrations\RollbackCommand;
use Illuminate\Database\Console\WipeCommand;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\WorkerIdle;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
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
            return new IdentityMigrationRepository($app->make(DatabaseManager::class), 'shared.migrations');
        });
    }

    public function boot(ConfigurationValidator $validator, RuntimeHeartbeat $heartbeat): void
    {
        Event::listen(WorkerIdle::class, fn () => $heartbeat->record('queue'));
        Event::listen(JobProcessed::class, fn () => $heartbeat->record('queue'));
        if (! $this->app->runningInConsole() && ($errors = $validator->errors()) !== []) {
            throw new RuntimeException('Invalid application configuration: '.implode(' ', $errors));
        }
    }
}
