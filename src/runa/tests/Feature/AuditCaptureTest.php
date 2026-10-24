<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Runa\Contracts\Modules\OperationAuthorizer;
use Runa\Contracts\Modules\OperationContext;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\Modules\Audit\Contracts\AuditEntity;
use Runa\Modules\Audit\Contracts\AuditEvent;
use Runa\Modules\Audit\Contracts\AuditQuery;
use Runa\Modules\Audit\Contracts\AuditReads;
use Runa\Modules\Audit\Contracts\AuditRecords;
use Runa\Modules\Audit\Contracts\AuditSuppression;
use Runa\Modules\Audit\Contracts\AuditUnavailable;
use Runa\Modules\Audit\Storage\AppendEvents;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationGuard;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Api\PrincipalScope;
use Runa\Support\Modules\ModuleRegistry;
use Tests\Fixtures\AuditCaptureFixture;

beforeEach(function (): void {
    // This fixture owns only the previously absent Audit schema in the guarded test DB.
    if (DB::getSchemaBuilder()->hasTable('runa_audit.events') || DB::selectOne("SELECT 1 FROM pg_namespace WHERE nspname = 'runa_audit'")) {
        throw new RuntimeException('Audit test fixture requires an absent disposable Audit schema.');
    }
    $this->auditFixtureCreated = false;
    if (DB::selectOne("SELECT 1 FROM pg_namespace WHERE nspname = 'app_audit_fixture'")) {
        throw new RuntimeException('Automatic capture requires an absent disposable fixture schema.');
    }
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
    DB::statement('CREATE SCHEMA app_audit_fixture');
    DB::statement('CREATE TABLE app_audit_fixture.records (id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY, public_id uuid NOT NULL UNIQUE, title text NOT NULL, details jsonb, created_at timestamptz, updated_at timestamptz, deleted_at timestamptz)');
    DB::table('shared.modules')->where('module_id', 'runa.audit')->update(['installed_version' => '0.4.0']);
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
        DB::statement('DROP TABLE app_audit_fixture.records RESTRICT');
        DB::statement('DROP SCHEMA app_audit_fixture RESTRICT');
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

it('rejects unsafe writes and rolls back automatic mutation even when Audit failure is caught', function (): void {
    $model = new AuditCaptureFixture(['title' => 'first', 'details' => '{"password":"fake-secret","nested":{"concealed":"fake-alias","safe":"visible"}}']);
    expect(fn () => $model->save())->toThrow(AuditUnavailable::class, 'before writing');
    expect(DB::table('app_audit_fixture.records')->count())->toBe(0);
    DB::transaction(fn () => $model->save());
    $entry = DB::table('runa_audit.events')->first();
    expect($entry->action)->toBe('record.created')->and($entry->changes)->not->toContain('fake-');
    DB::beginTransaction();
    $model->title = 'second';
    $model->save();
    DB::beginTransaction();
    $model->title = 'third';
    $model->save();
    DB::rollBack();
    expect(DB::table('app_audit_fixture.records')->value('title'))->toBe('second')
        ->and(DB::table('runa_audit.events')->count())->toBe(2);
    DB::rollBack();
    expect(DB::table('app_audit_fixture.records')->value('title'))->toBe('first')
        ->and(DB::table('runa_audit.events')->count())->toBe(1);
    DB::transaction(function () use ($model): void {
        DB::statement('ALTER TABLE runa_audit.events ADD CONSTRAINT fixture_reject CHECK (false) NOT VALID');
        $model->title = 'must roll back';
        expect(fn () => $model->save())->toThrow(AuditUnavailable::class);
        expect(DB::table('app_audit_fixture.records')->value('title'))->toBe('first');
        DB::statement('ALTER TABLE runa_audit.events DROP CONSTRAINT fixture_reject');
    });
    expect(DB::table('app_audit_fixture.records')->value('title'))->toBe('first');
    $fresh = new AuditCaptureFixture(['title' => 'new']);
    DB::transaction(function () use ($fresh): void {
        DB::statement('ALTER TABLE runa_audit.events ADD CONSTRAINT fixture_reject CHECK (false) NOT VALID');
        expect(fn () => $fresh->save())->toThrow(AuditUnavailable::class);
        expect($fresh->exists)->toBeFalse()->and($fresh->getKey())->toBeNull();
        DB::statement('ALTER TABLE runa_audit.events DROP CONSTRAINT fixture_reject');
    });
});

it('captures actual diffs snapshots soft deletion restore physical deletion and event toggles', function (): void {
    DB::transaction(function (): void {
        $model = AuditCaptureFixture::create(['title' => 'first']);
        $model->title = 'second';
        $model->saveQuietly();
        $model->save(); // no changed approved fields
        $model->delete();
        $model->restore();
        $model->snapshotCapture = true;
        $model->title = 'snapshot';
        $model->save();
        $snapshot = json_decode(DB::table('runa_audit.events')->orderByDesc('id')->value('changes'), true);
        expect($snapshot['before']['title'])->toBe('second')->and($snapshot['after']['title'])->toBe('snapshot');
        $model->forceDelete();
        expect(DB::table('runa_audit.events')->orderBy('id')->pluck('action')->all())->toBe([
            'record.created', 'record.updated', 'record.deleted', 'record.restored', 'record.updated', 'record.force_deleted',
        ]);
        $disabled = new AuditCaptureFixture(['title' => 'disabled']);
        $disabled->captureEvents = [];
        $disabled->save();
        $disabled->delete();
        $disabled->restore();
        $disabled->forceDelete();
        expect(DB::table('runa_audit.events')->count())->toBe(6);
        expect(fn () => $disabled->increment('id'))->toThrow(LogicException::class);
        expect(fn () => (new AuditCaptureFixture(['title' => 'ignore']))->saveOrIgnore())->toThrow(LogicException::class);
    });
});

it('unwinds nested suppression on exceptions while explicit relationship events and next work remain active', function (): void {
    $suppression = app(AuditSuppression::class);
    DB::transaction(function () use ($suppression): void {
        $suppression->run(function () use ($suppression): void {
            $first = AuditCaptureFixture::create(['title' => 'suppressed']);
            expect(fn () => $suppression->run(fn () => throw new RuntimeException('inner')))->toThrow(RuntimeException::class);
            expect($suppression->active())->toBeTrue();
            $api = app(ModuleApis::class)->resolve('runa.audit', AuditRecords::class, '1.0.0');
            $api->record(new AuditEvent('app.audit_fixture', 'records.relationship_change', entities: [
                new AuditEntity('app.audit_fixture', 'record', $first->public_id),
            ]));
        });
        expect($suppression->active())->toBeFalse();
        expect(fn () => $suppression->run(fn () => throw new RuntimeException('outer')))->toThrow(RuntimeException::class);
        AuditCaptureFixture::create(['title' => 'next work']);
    });
    expect($suppression->active())->toBeFalse()
        ->and(DB::table('runa_audit.events')->orderBy('id')->pluck('action')->all())->toBe(['records.relationship_change', 'record.created']);
    app()->forgetScopedInstances();
    expect(app(AuditSuppression::class)->active())->toBeFalse();
});

it('denies every Audit read by default and admits bounded immutable filtered cursor reads only through authority', function (): void {
    $apis = app(ModuleApis::class);
    $reads = $apis->resolve('runa.audit', AuditReads::class, '1.0.0');
    foreach ([fn () => $reads->page(new AuditQuery), fn () => $reads->metadata(), fn () => $reads->detail((string) Str::uuid7())] as $read) {
        expect($read)->toThrow(OperationRejected::class);
    }
    // Point 3 verifies the policy seam; Permissions supplies real grants in point 13.
    app()->bind(OperationAuthorizer::class, fn () => new class implements OperationAuthorizer
    {
        public function allows(OperationContext $context, string $permission): bool
        {
            return $permission === 'runa.audit.read' && app(PrincipalScope::class)->current() !== null;
        }
    });
    app()->forgetInstance(OperationGuard::class);
    app()->forgetInstance(ModuleApis::class);
    $reads = app(ModuleApis::class)->resolve('runa.audit', AuditReads::class, '1.0.0');
    $actor = (string) Str::uuid7();
    $entity = new AuditEntity('app.audit_fixture', 'record', (string) Str::uuid7());
    app(PrincipalScope::class)->run(new TrustedPrincipal($actor), function () use ($entity, $reads, $actor): void {
        $ids = [];
        for ($i = 0; $i < 3; $i++) {
            $ids[] = DB::transaction(fn ($connection) => app(AppendEvents::class)->record($connection,
                new AuditEvent('app.audit_fixture', 'record.large', entities: [$entity], changes: ['parts' => array_fill(0, 7, str_repeat('x', 8000))])))->publicId;
        }
        $query = new AuditQuery('app.audit_fixture', 2, action: 'record.large', actorPublicId: $actor, entity: $entity, outcome: 'success', from: '2020-01-01T00:00:00Z', until: '2100-01-01T00:00:00Z');
        $page = $reads->page($query);
        expect(array_map(fn ($e) => $e->reference->publicId, $page->entries))->toBe([$ids[2], $ids[1]])
            ->and($page->nextBefore)->toBe($ids[1])->and($page->entries[0]->changes)->toBeNull()
            ->and(strlen(json_encode($page)))->toBeLessThan(10000);
        $last = $reads->page(new AuditQuery(limit: 2, before: $page->nextBefore));
        expect(count($last->entries))->toBe(1)->and($last->nextBefore)->toBeNull();
        $detail = $reads->detail($ids[2]);
        expect(strlen(json_encode($detail->changes)))->toBeGreaterThan(55000)->and($detail->entities)->toEqual([$entity]);
        expect($reads->metadata()['max_page_size'])->toBe(100)->and($reads->detail((string) Str::uuid7()))->toBeNull();
        expect(fn () => $detail->module = 'app.forged')->toThrow(Error::class);
        expect(fn () => $reads->detail('invalid'))->toThrow(InvalidArgumentException::class);
        expect($reads->page(new AuditQuery(action: 'absent.action'))->entries)->toBe([]);
    });
    expect(fn () => $reads->metadata())->toThrow(OperationRejected::class);
    foreach ([fn () => new AuditQuery(limit: 101), fn () => new AuditQuery(action: 'unsafe raw'),
        fn () => new AuditQuery(from: '2026-02-30T00:00:00Z'), fn () => new AuditQuery(from: 'yesterday'),
        fn () => new AuditQuery(correlationId: str_repeat('x', 129))] as $invalid) {
        expect($invalid)->toThrow(InvalidArgumentException::class);
    }
});

it('captures each configured lifecycle event in diff and snapshot modes', function (bool $snapshot, string $enabled): void {
    DB::transaction(function () use ($snapshot, $enabled): void {
        $model = new AuditCaptureFixture(['title' => 'first']);
        $model->snapshotCapture = $snapshot;
        $model->captureEvents = [$enabled];
        $model->save();
        $model->title = 'changed';
        $model->save();
        $model->delete();
        $model->restore();
        $model->forceDelete();
        expect(DB::table('runa_audit.events')->pluck('action')->all())->toBe(['record.'.$enabled]);
        $changes = json_decode(DB::table('runa_audit.events')->value('changes'), true);
        if ($snapshot) {
            expect(array_keys($changes))->toEqualCanonicalizing(['before', 'after']);
            if ($enabled === 'created') {
                expect($changes['before'])->toBeNull();
            } elseif ($enabled === 'force_deleted') {
                expect($changes['after'])->toBeNull();
            }
        }
    });
})->with([false, true])->with(['created', 'updated', 'deleted', 'restored', 'force_deleted']);

it('uses persisted before state and fails before a suppressed write when Audit is unavailable', function (): void {
    DB::transaction(function (): void {
        $model = AuditCaptureFixture::create(['title' => 'original']);
        // Deliberate fixture setup simulates another writer without trusting stale model originals.
        DB::table('app_audit_fixture.records')->where('id', $model->id)->update(['title' => 'persisted']);
        $model->title = '[requested]';
        $model->save();
        $changes = json_decode(DB::table('runa_audit.events')->orderByDesc('id')->value('changes'), true);
        expect($changes['title'])->toEqual(['old' => 'persisted', 'new' => '[requested]']);
        $model->public_id = (string) Str::uuid7();
        expect(fn () => $model->save())->toThrow(AuditUnavailable::class, 'identity');
    });
    DB::table('shared.modules')->where('module_id', 'runa.audit')->update(['installed_version' => null]);
    DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    expect(fn () => DB::transaction(fn () => app(AuditSuppression::class)->run(fn () => AuditCaptureFixture::create(['title' => 'denied']))))
        ->toThrow(OperationRejected::class);
    expect(DB::table('app_audit_fixture.records')->count())->toBe(1);
});
