<?php

namespace App\Providers;

use App\Database\IdentityMigrationRepository;
use App\Support\ConfigurationValidator;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->extend(\Illuminate\Database\Console\Migrations\FreshCommand::class,
            fn ($command, Application $app) => $app->make(\App\Console\Commands\Database\GuardedFreshCommand::class));
        $this->app->extend(\Illuminate\Database\Console\Migrations\RefreshCommand::class,
            fn ($command, Application $app) => $app->make(\App\Console\Commands\Database\GuardedRefreshCommand::class));
        $this->app->extend(\Illuminate\Database\Console\Migrations\ResetCommand::class,
            fn ($command, Application $app) => $app->make(\App\Console\Commands\Database\GuardedResetCommand::class));
        $this->app->extend(\Illuminate\Database\Console\Migrations\RollbackCommand::class,
            fn ($command, Application $app) => $app->make(\App\Console\Commands\Database\GuardedRollbackCommand::class));
        $this->app->extend(\Illuminate\Database\Console\WipeCommand::class,
            fn ($command, Application $app) => $app->make(\App\Console\Commands\Database\GuardedWipeCommand::class));
        $this->app->extend('migration.repository', function (DatabaseMigrationRepository $repository, Application $app): IdentityMigrationRepository {
            return new IdentityMigrationRepository($app->make(\Illuminate\Database\DatabaseManager::class), 'shared.migrations');
        });
    }

    public function boot(ConfigurationValidator $validator): void
    {
        if (! $this->app->runningInConsole() && ($errors = $validator->errors()) !== []) {
            throw new RuntimeException('Invalid application configuration: '.implode(' ', $errors));
        }
    }
}
