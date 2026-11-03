<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Runa\Support\Seeding\DevelopmentFixtures;
use Runa\Support\Seeding\SeedGraph;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $graph = new SeedGraph(SeedGraph::registered());
        if (app()->environment('local', 'testing') && config('seeding.development_modules') === true) {
            $graph = app(DevelopmentFixtures::class)->graph(SeedGraph::registered());
        }
        $this->call($graph->plan('default', app()->environment()));
    }
}
