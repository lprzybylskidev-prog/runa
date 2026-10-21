<?php

namespace Runa\Console\Commands\Database;

use Illuminate\Database\DatabaseManager;
use Runa\Database\DatabaseScope;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait ScopesDatabase
{
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->laravel->make(DatabaseScope::class)->validate($input);
        $this->laravel->make(DatabaseScope::class)->assertHistory($this->laravel->make(DatabaseManager::class)->connection('pgsql'));

        parent::initialize($input, $output);
    }

    /** @return list<string> */
    protected function getMigrationPaths(): array
    {
        return $this->laravel->make(DatabaseScope::class)->paths($this->input);
    }
}
