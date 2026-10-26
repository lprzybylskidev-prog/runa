<?php

namespace Runa\Modules\Auth\Development;

use Runa\Support\Seeding\ManagedSeeder;
use Runa\Support\Seeding\SeedCategory;
use Runa\Support\Seeding\SeedContext;

final class AuthDevelopmentSeeder extends ManagedSeeder
{
    public static function category(): SeedCategory
    {
        return SeedCategory::Development;
    }

    protected function seed(SeedContext $context): void
    {
        app(Accounts::class)->populate();
    }
}
