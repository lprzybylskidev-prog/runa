<?php

namespace Runa\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Console\ConfigMakeCommand;
use Illuminate\Foundation\Console\TestMakeCommand;
use Illuminate\Support\ServiceProvider;
use NunoMaduro\Collision\Adapters\Laravel\Commands\TestCommand;
use Runa\Console\Commands\CheckTestDatabase;
use Runa\Console\Commands\DevelopmentAccounts;
use Runa\Console\Commands\Generators\ApplicationConfigMakeCommand;
use Runa\Console\Commands\Generators\ApplicationTestMakeCommand;
use Runa\Console\Commands\MakeModule;
use Runa\Testing\Backend\ApplicationTestCommand;

final class DevelopmentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->extend(ConfigMakeCommand::class,
            fn ($command, Application $app) => $app->make(ApplicationConfigMakeCommand::class));
        $this->app->extend(TestMakeCommand::class,
            fn ($command, Application $app) => $app->make(ApplicationTestMakeCommand::class));
        if ($this->app->environment(['local', 'testing']) && class_exists(TestCommand::class)) {
            $this->app->extend(TestCommand::class,
                fn ($command, Application $app) => $app->make(ApplicationTestCommand::class));
        }
        if ($this->app->environment(['local', 'testing'])) {
            $this->commands([MakeModule::class, CheckTestDatabase::class, DevelopmentAccounts::class]);
        }
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole() && $this->app->environment(['local', 'testing'])) {
            $commands = base_path('runa/Tooling/Management/commands.php');
            if (is_file($commands)) {
                $this->commands(require $commands);
            }
        }
    }
}
