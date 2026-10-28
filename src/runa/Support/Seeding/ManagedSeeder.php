<?php

namespace Runa\Support\Seeding;

use Illuminate\Database\Seeder;
use RuntimeException;

abstract class ManagedSeeder extends Seeder
{
    abstract public static function category(): SeedCategory;

    /** @return list<class-string<ManagedSeeder>> */
    public static function dependsOn(): array
    {
        return [];
    }

    final public function run(): void
    {
        if (static::category() === SeedCategory::Development) {
            if (! app()->environment(['local', 'testing'])) {
                throw new RuntimeException('Development seeders require local or testing environment.');
            }
            DevelopmentFixtures::assertAllowed();
        }
        $options = static::category() === SeedCategory::Development
            ? config('seeding.development.'.static::class, config('seeding.development.'.class_basename(static::class), [])) : [];
        if (! is_array($options)) {
            throw new RuntimeException('Seeder options must be an array.');
        }
        $this->seed(new SeedContext($options));
    }

    abstract protected function seed(SeedContext $context): void;
}
