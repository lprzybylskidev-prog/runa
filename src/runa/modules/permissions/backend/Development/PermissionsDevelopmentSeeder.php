<?php

namespace Runa\Modules\Permissions\Development;

use Runa\Support\Seeding\ManagedSeeder;
use Runa\Support\Seeding\SeedCategory;
use Runa\Support\Seeding\SeedContext;

final class PermissionsDevelopmentSeeder extends ManagedSeeder
{
    public static function category(): SeedCategory
    {
        return SeedCategory::Development;
    }

    protected function seed(SeedContext $context): void
    {
        app(Access::class)->populate();
    }
}
