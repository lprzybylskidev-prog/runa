<?php

namespace Runa\Database\Seeders;

use Illuminate\Database\Seeder;
use Runa\Support\Seeding\SeedGraph;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call((new SeedGraph(SeedGraph::discover()))->plan('default', app()->environment()));
    }
}
