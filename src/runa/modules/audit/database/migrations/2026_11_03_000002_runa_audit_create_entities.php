<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Runa\Support\Modules\Data\ModuleSchema;
use Runa\Support\Modules\ModuleIdentity;

return new class extends Migration
{
    public function up(): void
    {
        $connection = DB::connection();
        $schema = new ModuleSchema($connection, new ModuleIdentity('runa.audit'));
        $schema->create('entities', function (Blueprint $table): void {
            $table->bigInteger('event_id');
            $table->string('module', 64);
            $table->string('entity_type', 64);
            $table->uuid('entity_public_id');
            $table->foreign('event_id', 'audit_entities_event_fk')->references('id')->on('runa_audit.events')->restrictOnDelete()->restrictOnUpdate();
            $table->unique(['event_id', 'module', 'entity_type', 'entity_public_id'], 'audit_entities_unique');
            $table->index(['module', 'entity_type', 'entity_public_id', 'event_id'], 'audit_entities_lookup');
        }, exposed: false);
        $connection->statement("ALTER TABLE runa_audit.entities ADD CHECK (entity_public_id::text ~ '^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$')");
    }

    public function down(): void
    {
        throw new RuntimeException('Module table recovery requires an explicit plan.');
    }
};
