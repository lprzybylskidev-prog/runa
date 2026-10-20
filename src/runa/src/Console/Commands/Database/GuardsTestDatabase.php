<?php

namespace Runa\Console\Commands\Database;

use Runa\Support\TestDatabaseGuard;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait GuardsTestDatabase
{
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $selected = $input->getOption('database');
        foreach ($selected === null ? [] : (array) $selected as $connection) {
            if ($connection !== 'pgsql') {
                throw new RuntimeException('Destructive database operations only support the guarded pgsql connection.');
            }
        }
        $this->laravel->make(TestDatabaseGuard::class)->assertSafe();

        parent::initialize($input, $output);
    }
}
