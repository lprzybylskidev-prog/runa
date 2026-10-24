<?php

use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Runa\Contracts\Modules\PreparationContext;
use Runa\Contracts\Modules\SettingsContext;
use Runa\Contracts\Modules\SettingsPublication;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\Modules\Audit\Actions\RecordAttempt;
use Runa\Modules\Audit\Contracts\AuditEntity;
use Runa\Modules\Audit\Contracts\AuditEvent;
use Runa\Modules\Audit\Contracts\AuditRecords;
use Runa\Modules\Audit\Contracts\AuditUnavailable;
use Runa\Modules\Audit\Storage\AppendEvents;
use Runa\Modules\Auth\Contracts\AuthorizationIdentity;
use Runa\Support\Logging\LogContext;
use Runa\Support\Logging\QueueLogContext;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Api\PrincipalScope;
use Runa\Support\Modules\AuditParticipation;
use Runa\Support\Modules\Development\ModuleContractSuite;
use Runa\Support\Modules\ModuleRegistry;
use Runa\Support\Modules\Updates\ModuleUpdates;

beforeEach(function (): void {
    // This fixture owns only the previously absent Audit schema in the guarded test DB.
    if (DB::getSchemaBuilder()->hasTable('runa_audit.events') || DB::selectOne("SELECT 1 FROM pg_namespace WHERE nspname = 'runa_audit'")) {
        throw new RuntimeException('Audit test fixture requires an absent disposable Audit schema.');
    }
    $this->auditFixtureCreated = false;
    if (DB::selectOne("SELECT 1 FROM pg_namespace WHERE nspname = 'runa_auth'")) {
        throw new RuntimeException('Audit fixture requires an absent disposable Auth schema.');
    }
    $this->authFixtureCreated = false;
    app(ModuleRegistry::class)->synchronize('runa');
    // The newly supplied Core must be genuinely ready, not a fabricated installed flag.
    $authMigration = require base_path('runa/modules/auth/database/migrations/2026_10_23_000001_runa_auth_identities.php');
    $authMigration->up();
    (require base_path('runa/modules/auth/database/migrations/2026_10_23_000002_runa_auth_entry_tokens.php'))->up();
    $this->authFixtureCreated = true;
    DB::table('shared.modules')->where('module_id', 'runa.auth')->update(['installed_version' => '0.2.0']);
    $migration = require base_path('runa/modules/audit/database/migrations/2026_10_23_000001_runa_audit_events.php');
    $migration->up();
    $this->auditFixtureCreated = true;
    DB::table('shared.modules')->where('module_id', 'runa.audit')->update(['installed_version' => app(ModuleRegistry::class)->snapshot()->modules['runa.audit']->sourceVersion]);
    DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
});

afterEach(function (): void {
    while (DB::connection()->transactionLevel() > 0) {
        DB::connection()->rollBack();
    }
    if ($this->authFixtureCreated ?? false) {
        DB::statement('DROP TABLE runa_auth.entry_tokens RESTRICT');
        DB::statement('DROP TABLE runa_auth.users RESTRICT');
        DB::statement('DROP SCHEMA runa_auth RESTRICT');
        $authId = DB::table('shared.modules')->where('module_id', 'runa.auth')->value('id');
        DB::table('shared.module_update_steps')->where('module_id', $authId)->delete();
        DB::table('shared.modules')->where('module_id', 'runa.auth')->delete();
    }
    if ($this->auditFixtureCreated ?? false) {
        // Exact fixture-created tables only; no durable development/test data or global prune.
        DB::statement('DROP TABLE runa_audit.entities RESTRICT');
        DB::statement('DROP TABLE runa_audit.events RESTRICT');
        DB::statement('DROP SCHEMA runa_audit RESTRICT');
        $id = DB::table('shared.modules')->where('module_id', 'runa.audit')->value('id');
        DB::table('shared.module_update_steps')->where('module_id', $id)->delete();
        DB::table('shared.modules')->where('module_id', 'runa.audit')->delete();
        DB::table('shared.module_registry_state')->where('id', 1)->update(['update_operation' => null, 'update_owner' => null, 'update_fingerprint' => null]);
        DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    }
    DB::purge('audit_other');
});

it('keeps manual Audit atomic across real root rollback savepoints and write failure', function (): void {
    $connection = DB::connection();
    $writer = app(AppendEvents::class);
    $event = new AuditEvent('app.sales', 'order.change', entities: [
        new AuditEntity('app.sales', 'order', (string) Str::uuid7()),
        new AuditEntity('app.sales', 'customer', (string) Str::uuid7()),
    ], changes: ['status' => ['old' => 'draft', 'new' => 'approved']]);
    expect(fn () => $writer->record($connection, $event))->toThrow(AuditUnavailable::class, 'active default');
    config(['database.connections.audit_other' => config('database.connections.pgsql')]);
    DB::connection('audit_other')->transaction(function ($other) use ($writer, $event): void {
        expect(fn () => $writer->record($other, $event))->toThrow(AuditUnavailable::class, 'active default');
    });
    $connection->beginTransaction();
    $first = $writer->record($connection, $event);
    $connection->beginTransaction();
    $second = $writer->record($connection, $event);
    $connection->rollBack();
    expect(DB::table('runa_audit.events')->where('public_id', $first->publicId)->exists())->toBeTrue()
        ->and(DB::table('runa_audit.events')->where('public_id', $second->publicId)->exists())->toBeFalse()
        ->and(DB::table('runa_audit.entities')->count())->toBe(2);
    $connection->rollBack();
    expect(DB::table('runa_audit.events')->count())->toBe(0);
    $before = DB::table('shared.module_registry_state')->where('id', 1)->value('generation');
    expect(fn () => $connection->transaction(function () use ($connection, $writer, $event): void {
        DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
        $connection->statement('ALTER TABLE runa_audit.events ADD CONSTRAINT fixture_refusal CHECK (false)');
        $writer->record($connection, $event);
    }))->toThrow(AuditUnavailable::class);
    expect(DB::table('shared.module_registry_state')->where('id', 1)->value('generation'))->toBe($before)
        ->and(DB::table('runa_audit.events')->count())->toBe(0);
});

it('persists independent refusal only after root rollback and reports unavailable history', function (): void {
    $connection = DB::connection();
    $attempts = app(RecordAttempt::class);
    $event = new AuditEvent('app.sales', 'order.change', 'refused');
    $connection->beginTransaction();
    expect(fn () => $attempts->record($event))->toThrow(AuditUnavailable::class, 'root rollback');
    $connection->beginTransaction();
    expect(fn () => $attempts->record($event))->toThrow(AuditUnavailable::class);
    $connection->rollBack();
    expect(fn () => $attempts->record($event))->toThrow(AuditUnavailable::class);
    $connection->rollBack();
    $reference = $attempts->record($event);
    $connection->beginTransaction();
    app(AppendEvents::class)->record($connection, new AuditEvent('app.sales', 'order.change'));
    $connection->rollBack();
    expect(DB::table('runa_audit.events')->pluck('public_id')->all())->toBe([$reference->publicId]);
    expect(fn () => $attempts->record(new AuditEvent('app.sales', 'order.change')))->toThrow(AuditUnavailable::class);
    DB::statement('ALTER TABLE runa_audit.events RENAME TO fixture_unavailable');
    try {
        expect(fn () => $attempts->record($event))->toThrow(AuditUnavailable::class, 'no audited refusal');
    } finally {
        DB::statement('ALTER TABLE runa_audit.fixture_unavailable RENAME TO events');
    }
});

it('redacts nested credentials refuses unsafe values and resolves trusted actors without spoofing', function (): void {
    $event = new AuditEvent('app.sales', 'order.change', changes: [
        'status' => ['old' => 'draft', 'new' => 'approved'],
        'nested' => ['password_hash' => 'fake-secret', 'totpSecret' => 'fake-secret', 'other' => 'Bearer fake-secret'],
        'alias' => ['concealed' => 'fake-aliased'],
        'recovery_codes' => ['fake-code'], 'session' => 'fake-session', 'invitationToken' => 'fake-invitation',
    ], context: ['actor' => 'untrusted-description'], sensitiveFields: ['concealed']);
    expect(json_encode($event->changes))->not->toContain('fake-');
    foreach ([['object' => new stdClass], ['long' => str_repeat('a', 8193)], ['many' => array_fill(0, 1100, 1)], ['bad' => INF]] as $unsafe) {
        expect(fn () => new AuditEvent('app.sales', 'order.change', context: $unsafe))->toThrow(InvalidArgumentException::class);
    }
    $logs = app(LogContext::class);
    $principals = app(PrincipalScope::class);
    $id = (string) Str::uuid7();
    $logs->run(['request_id' => 'server-request', 'correlation_id' => 'server-correlation'], function () use ($event, $principals, $id): void {
        $principals->run(new TrustedPrincipal($id), fn () => DB::transaction(fn ($connection) => app(AppendEvents::class)->record($connection, $event)));
    });
    $row = DB::table('runa_audit.events')->first();
    expect($row->actor_public_id)->toBe($id)->and($row->actor_kind)->toBe('human')
        ->and($row->request_id)->toBe('server-request')->and($row->correlation_id)->toBe('server-correlation');
    DB::transaction(fn ($connection) => app(AppendEvents::class)->record($connection, $event));
    expect(DB::table('runa_audit.events')->orderByDesc('id')->value('actor_public_id'))->toBeNull();
});

it('participates in settings atomically and refuses forged reserved preparation', function (): void {
    $participant = app(AuditParticipation::class);
    $context = new SettingsContext('operation-a', 'runa.audit', 0, 'testing', [], null, now()->toIso8601String());
    expect(fn () => DB::transaction(function ($connection) use ($participant, $context): void {
        $participant->publishing($connection, new SettingsPublication($context, 1, 'publish', ['secret' => ['replaced' => true]]));
        throw new RuntimeException('root failure');
    }))->toThrow(RuntimeException::class, 'root failure');
    expect(DB::table('runa_audit.events')->count())->toBe(0);
    DB::transaction(fn ($connection) => $participant->publishing($connection, new SettingsPublication($context, 1, 'restore', [])));
    expect(DB::table('runa_audit.events')->value('action'))->toBe('settings.restore');
    expect(fn () => DB::transaction(fn ($connection) => $participant->preparing(new PreparationContext(
        $connection, 'runa', 'forged', 'runa.audit', 'step', 'fingerprint', 'hash', 'reservation',
    ))))->toThrow(RuntimeException::class, 'reservation');
});

it('installs the real Audit store through reserved updates once and keeps ordinary ingress fenced', function (): void {
    DB::statement('DROP TABLE runa_audit.entities RESTRICT');
    DB::statement('DROP TABLE runa_audit.events RESTRICT');
    DB::statement('DROP SCHEMA runa_audit RESTRICT');
    DB::table('shared.modules')->where('module_id', 'runa.audit')->update(['installed_version' => null]);
    DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    $updates = app(ModuleUpdates::class);
    $updates->apply('runa', str_repeat('d', 40), static function (): void {});
    expect(DB::table('runa_audit.events')->pluck('action')->all())->toBe(['audit.store_ready']);
    $updates->apply('runa', str_repeat('d', 40), static function (): void {});
    expect(DB::table('runa_audit.events')->count())->toBe(1);
    $record = app(ModuleRegistry::class)->snapshot()->modules['runa.audit'];
    expect((new ModuleContractSuite(base_path(), true))->runtime($record))->toBe([]);
    $api = app(ModuleApis::class)->resolve('runa.audit', AuditRecords::class, '1.0.0');
    DB::transaction(fn () => $api->record(new AuditEvent('app.sales', 'order.approve')));
    DB::table('shared.module_registry_state')->where('id', 1)->update(['update_operation' => str_repeat('e', 40), 'update_owner' => 'runa', 'update_fingerprint' => str_repeat('f', 64)]);
    expect(fn () => DB::transaction(fn () => $api->record(new AuditEvent('app.sales', 'order.approve'))))->toThrow(OperationRejected::class);
});

it('attributes sequential queue work without inheriting human actors or request secrets', function (): void {
    $job = $this->createStub(Job::class);
    $job->method('payload')->willReturn(['displayName' => 'App\\Jobs\\ApprovedJob']);
    $job->method('getJobId')->willReturn('job-7');
    $job->method('getQueue')->willReturn('default');
    $queue = app(QueueLogContext::class);
    $id = (string) Str::uuid7();
    app(LogContext::class)->run(['request_id' => 'old-human-request'], function () use ($job, $queue, $id): void {
        app(PrincipalScope::class)->run(new TrustedPrincipal($id), function () use ($job, $queue, $id): void {
            $queue->before(new JobProcessing('database', $job));
            try {
                DB::transaction(fn ($connection) => app(AppendEvents::class)->record($connection, new AuditEvent('app.sales', 'job.record')));
                throw new RuntimeException('work failed');
            } catch (RuntimeException $error) {
                $queue->after(new JobExceptionOccurred('database', $job, $error));
            }
            $row = DB::table('runa_audit.events')->orderByDesc('id')->first();
            expect($row->actor_kind)->toBe('queue')->and($row->actor_public_id)->toBeNull()->and($row->request_id)->toBeNull();
            $execution = json_decode($row->execution, true, flags: JSON_THROW_ON_ERROR);
            expect($execution['job_class'])->toBe('App\\Jobs\\ApprovedJob')->and($execution['job_id'])->toBe('job-7')
                ->and($queue->current())->toBe([]);
            DB::transaction(fn ($connection) => app(AppendEvents::class)->record($connection, new AuditEvent('app.sales', 'http.record')));
            expect(DB::table('runa_audit.events')->orderByDesc('id')->value('actor_public_id'))->toBe($id);
        });
    });
    DB::transaction(fn ($connection) => app(AppendEvents::class)->record($connection, new AuditEvent('app.sales', 'system.record')));
    expect(DB::table('runa_audit.events')->orderByDesc('id')->value('actor_public_id'))->toBeNull();
});

it('keeps independent Audit available when optional Auth actor enrichment cannot resolve', function (): void {
    app()->bind(AuthorizationIdentity::class, function (): never {
        throw new RuntimeException('Optional Auth source unavailable.');
    });
    $principal = new TrustedPrincipal((string) Str::uuid7());
    app(PrincipalScope::class)->run($principal, function (): void {
        DB::transaction(fn () => app(AppendEvents::class)->record(DB::connection(), new AuditEvent('app.sales', 'order.changed')));
    });
    $event = DB::table('runa_audit.events')->first();
    expect($event->actor_kind)->toBe('human')->and($event->actor_public_id)->toBe($principal->publicId)
        ->and($event->actor_label)->toBeNull();
});
