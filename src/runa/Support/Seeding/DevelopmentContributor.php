<?php

namespace Runa\Support\Seeding;

/** Explicit trusted module metadata; never folder discovery or request-selected classes. */
interface DevelopmentContributor
{
    /** @return list<string> Explicit contributor module dependencies. */
    public function dependsOn(): array;

    /** @return list<class-string<ManagedSeeder>> */
    public function seeders(): array;
}
