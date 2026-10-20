<?php

namespace Runa\Console\Commands\Database;

use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Database\Seeder;
use Runa\Database\DatabaseScope;

final class ScopedSeedCommand extends SeedCommand
{
    use ScopesDatabase;

    protected function getSeeder()
    {
        $scope = $this->laravel->make(DatabaseScope::class);
        $class = $this->input->getArgument('class') ?? $this->input->getOption('class');
        if (! is_string($class)) {
            throw new \RuntimeException('Seeder must be a class name.');
        }
        if ($scope->platform && $class === 'Database\\Seeders\\DatabaseSeeder') {
            $class = 'Runa\\Database\\Seeders\\DatabaseSeeder';
        }
        $class = $scope->seeder($class);

        $seeder = $this->laravel->make($class);
        if (! $seeder instanceof Seeder) {
            throw new \RuntimeException('Invalid scoped seeder.');
        }

        return $seeder->setContainer($this->laravel)->setCommand($this);
    }
}
