<?php

namespace Runa\Database;

use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Schema\Blueprint;

final class IdentityMigrationRepository extends DatabaseMigrationRepository
{
    public function createRepository(): void
    {
        $this->getConnection()->statement('CREATE SCHEMA IF NOT EXISTS shared');
        $this->getConnection()->getSchemaBuilder()->create('shared.migrations', function (Blueprint $table): void {
            $table->bigIncrements('id')->generatedAs()->always();
            $table->text('migration');
            $table->integer('batch');
        });
    }
}
