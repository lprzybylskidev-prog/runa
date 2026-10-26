<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::connection()->statement('CREATE INDEX audit_events_action_read ON runa_audit.events (action, id)');
        DB::connection()->statement('CREATE INDEX audit_events_outcome_read ON runa_audit.events (outcome, id)');
    }

    public function down(): void
    {
        throw new RuntimeException('Audit downgrade requires an explicit forward recovery plan.');
    }
};
