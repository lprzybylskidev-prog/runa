<?php

namespace App\Support\Seeding;

enum SeedCategory: string
{
    case Production = 'production';
    case Development = 'development';
    case Occasional = 'occasional';
}
