<?php

namespace Database\Seeders;

use App\Support\Seeding\SeedGraph;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call((new SeedGraph(SeedGraph::discover()))->plan('default', app()->environment()));
    }
}
