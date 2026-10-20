<?php

namespace Runa\Console\Commands\Database;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Console\Migrations\FreshCommand;
use Illuminate\Database\Events\DatabaseRefreshed;

final class GuardedFreshCommand extends FreshCommand
{
    use GuardsTestDatabase;

    public function handle(): int
    {
        if ($this->isProhibited() || ! $this->confirmToProceed()) {
            return self::FAILURE;
        }
        $database = $this->option('database');
        $result = $this->call('db:wipe', array_filter(['--database' => $database, '--force' => true]));
        if ($result !== 0) {
            return $result;
        }
        $result = $this->call('migrate', array_filter(['--database' => $database, '--force' => true,
            '--path' => $this->option('path'), '--realpath' => $this->option('realpath'), '--step' => $this->option('step')]));
        if ($result !== 0) {
            return $result;
        }
        if ($this->laravel->bound(Dispatcher::class)) {
            $this->laravel->make(Dispatcher::class)->dispatch(new DatabaseRefreshed($database, $this->needsSeeding()));
        }
        if ($this->needsSeeding()) {
            return $this->call('db:seed', array_filter(['--database' => $database, '--force' => true, '--class' => $this->option('seeder')]));
        }

        return self::SUCCESS;
    }
}
