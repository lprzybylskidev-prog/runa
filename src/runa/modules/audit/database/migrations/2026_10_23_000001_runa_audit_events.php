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
        $schema->create('events', function (Blueprint $table): void {
            $table->timestampTz('occurred_at', 6);
            $table->string('module', 64);
            $table->string('action', 128);
            $table->string('outcome', 16);
            $table->string('actor_kind', 16);
            $table->uuid('actor_public_id')->nullable();
            $table->string('actor_label', 160)->nullable();
            foreach (['request_id', 'correlation_id', 'operation_id'] as $key) {
                $table->string($key, 128)->nullable();
            }
            $table->jsonb('changes');
            $table->jsonb('context');
            $table->jsonb('execution');
            $table->index(['occurred_at', 'id'], 'audit_events_time');
            $table->index(['module', 'action', 'id'], 'audit_events_action');
            $table->index(['actor_public_id', 'id'], 'audit_events_actor');
            $table->index(['correlation_id', 'id'], 'audit_events_correlation');
        });
        $connection->statement("ALTER TABLE runa_audit.events ADD CHECK (outcome IN ('success','attempt','refused','failure')), ADD CHECK (actor_kind IN ('human','anonymous','system','scheduler','queue','cli','machine')), ADD CHECK ((actor_kind IN ('human','machine')) = (actor_public_id IS NOT NULL)), ADD CHECK (actor_public_id IS NULL OR actor_public_id::text ~ '^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$')");
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
        throw new RuntimeException('Audit history is retained indefinitely; destructive rollback is refused.');
    }
};
