<?php

namespace Runa\Console\Commands\Database;

use Illuminate\Database\DatabaseManager;
use Runa\Database\DatabaseScope;
use Runa\Testing\Database\TestDatabaseGuard;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait GuardsTestDatabase
{
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->laravel->make(DatabaseScope::class)->validate($input);
        $selected = $input->getOption('database');
        foreach ($selected === null ? [] : (array) $selected as $connection) {
            if ($connection !== 'pgsql') {
                throw new RuntimeException('Destructive database operations only support the guarded pgsql connection.');
            }
        }
        // Production excludes developer test support; refuse before resolving it.
        if ($this->laravel['config']->get('app.env') !== 'testing') {
            throw new RuntimeException('Destructive database operations require the isolated PostgreSQL testing environment, host, role and database.');
        }
        $this->laravel->make(TestDatabaseGuard::class)->assertSafe();

        $this->laravel->make(DatabaseScope::class)->assertHistory($this->laravel->make(DatabaseManager::class)->connection('pgsql'));

        parent::initialize($input, $output);
    }
}
