<?php

use App\Modules\Reference\Definitions\ReferencePermissions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class);

/*
 * Test-only PostgreSQL fixtures use retained technical/Core schemas, never foreign
 * private classes or owner test helpers. runa:dev:initialize must have prepared
 * the isolated test database's Core. Actual business assertions use named HTTP.
 * The supported runner guards database identity before this fixture executes.
 */
beforeEach(function (): void {
    $this->travelTo(now()->startOfSecond());
    foreach (['runa_auth.users', 'runa_audit.events', 'runa_permissions.permissions'] as $table) {
        if (! Schema::hasTable($table)) {
            throw new RuntimeException('Prepare the isolated test Core with runa:dev:initialize before reference tests.');
        }
    }
    $this->referenceOriginal = DB::table('shared.modules')->where('module_id', 'app.reference')->first();
    $this->referenceFixtureOwned = ! Schema::hasTable('app_reference.samples');
    expect(Artisan::call('runa:modules:sync', ['owner' => 'app']))->toBe(0);
    // Test fixture metadata is not an installation/enable workflow example.
    DB::table('shared.modules')->where('module_id', 'app.reference')->update(['installed_version' => '0.4.0', 'enabled' => true]);
    DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
    if ($this->referenceFixtureOwned) {
        foreach (glob(base_path('app/modules/reference/database/migrations/*.php')) as $migration) {
            (require $migration)->up();
        }
    }
    if (! Schema::hasColumn('app_reference.samples', 'revision')) {
        throw new RuntimeException('Explicitly update reference test storage to 0.4.0 before testing; fixtures never reset existing data.');
    }
    $this->refreshApplication();
    $this->fixtureId = (string) Str::uuid7();
    $this->fixtureCode = 'case_'.bin2hex(random_bytes(5));
    DB::table('app_reference.samples')->insert(['public_id' => $this->fixtureId, 'code' => $this->fixtureCode, 'revision' => 1, 'created_at' => now(), 'updated_at' => now()]);
    $this->actorId = (string) Str::uuid7();
    $this->actorEmail = 'reference-'.bin2hex(random_bytes(6)).'@example.test';
    DB::table('runa_auth.users')->insert(['public_id' => $this->actorId, 'name' => 'Reference test', 'email' => $this->actorEmail,
        'email_claim' => $this->actorEmail, 'password' => Hash::make('reference-example-password'), 'password_changed_at' => now(),
        'active' => true, 'email_verified_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
    $this->createdPermissions = [];
    foreach (ReferencePermissions::permissions() as $permission) {
        $code = 'app.reference.'.$permission->key;
        if (! DB::table('runa_permissions.permissions')->where('code', $code)->exists()) {
            $this->createdPermissions[] = $code;
            DB::table('runa_permissions.permissions')->insert(['public_id' => (string) Str::uuid7(), 'code' => $code,
                'module_id' => 'app.reference', 'name_pl' => $permission->names['pl'], 'name_en' => $permission->names['en'],
                'description_pl' => $permission->descriptions['pl'], 'description_en' => $permission->descriptions['en'],
                'created_at' => now(), 'updated_at' => now()]);
        }
    }
    $this->grant = function (array $codes): void {
        foreach ($codes as $code) {
            $id = DB::table('runa_permissions.permissions')->where('code', str_contains($code, 'runa.') ? $code : 'app.reference.'.$code)->value('id');
            if ($id === null) {
                throw new RuntimeException('Missing fixture permission.');
            }
            DB::table('runa_permissions.user_permissions')->insertOrIgnore(['user_public_id' => $this->actorId,
                'permission_id' => $id, 'created_at' => now(), 'updated_at' => now()]);
        }
    };
    $this->proof = fn (string $action) => $this->postJson('/api/runa/auth/step-up', [
        'action' => $action, 'current_password' => 'reference-example-password'])->assertOk();
    config(['queue.default' => 'database', 'queue.connections.database.queue' => 'reference-example-test']);
    $this->postJson('/api/runa/auth/login', ['identifier' => $this->actorEmail, 'password' => 'reference-example-password'])->assertOk();
    $this->withCredentials()->withCookie('runa_session', app('session')->getId());
});

afterEach(function (): void {
    $this->travelBack();
    if (! isset($this->actorId)) {
        return;
    }
    DB::statement('ALTER TABLE runa_audit.events DROP CONSTRAINT IF EXISTS reference_test_audit_refusal');
    // Retain immutable Audit and Core identity history. These fixture accounts are
    // made ineligible; disposable parallel workers are removed by the supported runner.
    DB::table('runa_auth.users')->where('public_id', $this->actorId)->update(['active' => false]);
    DB::table('runa_permissions.user_permissions')->where('user_public_id', $this->actorId)->delete();
    DB::table('app_reference.samples')->where('public_id', $this->fixtureId)->update(['deleted_at' => now()]);
    if ($this->referenceOriginal !== null) {
        $original = (array) $this->referenceOriginal;
        unset($original['id']);
        DB::table('shared.modules')->where('id', $this->referenceOriginal->id)->update($original);
    } else {
        DB::table('shared.modules')->where('module_id', 'app.reference')->update(['enabled' => false]);
    }
    DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
});

it('omits the separately granted field and refuses reads and writes independently', function (): void {
    $this->getJson('/api/app/reference/samples')->assertForbidden();
    ($this->grant)(['samples.read']);
    $response = $this->getJson('/api/app/reference/samples')->assertOk()->json('data.items');
    $record = collect($response)->firstWhere('public_id', $this->fixtureId);
    expect($record)->not->toHaveKey('code');
    $this->patchJson('/api/app/reference/samples', ['public_id' => $this->fixtureId, 'revision' => '1', 'code' => 'changed'])->assertForbidden();
    ($this->grant)(['samples.write']);
    $this->patchJson('/api/app/reference/samples', ['public_id' => $this->fixtureId, 'revision' => '1', 'code' => 'changed'])->assertForbidden();
    expect(DB::table('app_reference.samples')->where('public_id', $this->fixtureId)->value('code'))->toBe($this->fixtureCode);
});

it('audits a sample edit and rejects stale or deleted records without overwriting', function (): void {
    ($this->grant)(['samples.read', 'samples.write', 'samples.code.read', 'samples.code.write']);
    $input = ['public_id' => $this->fixtureId, 'revision' => '1', 'code' => 'changed_'.bin2hex(random_bytes(4))];
    $response = $this->patchJson('/api/app/reference/samples', $input);
    expect($response->status())->toBe(200, $response->getContent());
    $response->assertJsonPath('data.revision', '2')->assertJsonPath('data.code', $input['code']);
    expect(DB::table('runa_audit.entities')->where('entity_public_id', $this->fixtureId)->exists())->toBeTrue();
    $this->patchJson('/api/app/reference/samples', $input)->assertConflict();
    DB::table('app_reference.samples')->where('public_id', $this->fixtureId)->update(['deleted_at' => now()]);
    $this->patchJson('/api/app/reference/samples', [...$input, 'revision' => '2'])->assertNotFound();
});

it('rolls the sample and revision back when mandatory automatic Audit refuses', function (): void {
    ($this->grant)(['samples.read', 'samples.write', 'samples.code.write']);
    DB::statement("ALTER TABLE runa_audit.events ADD CONSTRAINT reference_test_audit_refusal CHECK (module <> 'app.reference') NOT VALID");
    $this->patchJson('/api/app/reference/samples', ['public_id' => $this->fixtureId, 'revision' => '1', 'code' => 'rollback'])->assertStatus(500);
    $record = DB::table('app_reference.samples')->where('public_id', $this->fixtureId)->first();
    expect($record->code)->toBe($this->fixtureCode)->and($record->revision)->toBe(1);
});

it('requires exact settings review and one own proof while keeping secret projections safe', function (): void {
    ($this->grant)(['settings.read', 'settings.write', 'settings.credential.write']);
    $state = $this->getJson('/api/app/reference/settings')->assertOk()->json('data');
    expect($state['settings'])->not->toHaveKey('credential');
    $this->travel(1)->seconds();
    $renewed = $this->getJson('/api/app/reference/settings')->assertOk()->json('data');
    expect($renewed['review_id'])->toBe($state['review_id']);
    $input = ['revision' => $state['revision'], 'review_id' => $state['review_id'], 'values' => ['label' => 'Reference test', 'credential' => 'test-private-value']];
    $response = $this->postJson('/api/app/reference/settings/review', $input);
    expect($response->status())->toBe(200, $response->getContent());
    $review = $response->assertJsonMissing(['test-private-value'])->json('data');
    expect($review['changes']['credential'])->toBe(['replaced' => true]);
    $this->postJson('/api/app/reference/settings/publish', [...$input, 'review' => $review['review']])->assertForbidden();
    ($this->proof)('runa.admin.enter');
    $this->postJson('/api/app/reference/settings/publish', [...$input, 'review' => $review['review']])->assertForbidden();
    ($this->proof)('app.reference.settings.publish');
    $this->postJson('/api/app/reference/settings/publish', [...$input, 'values' => ['label' => 'Other'], 'review' => $review['review']])->assertConflict();
    $this->postJson('/api/app/reference/settings/publish', [...$input, 'review' => $review['review']])->assertOk()->assertJsonPath('data.status', 'succeeded');
    $state = $this->getJson('/api/app/reference/settings')->assertOk()->json('data');
    $next = ['revision' => $state['revision'], 'review_id' => $state['review_id'], 'values' => ['credential' => null]];
    $nextReview = $this->postJson('/api/app/reference/settings/review', $next)->assertOk()->json('data.review');
    $this->postJson('/api/app/reference/settings/publish', [...$next, 'review' => $nextReview])->assertForbidden();
    ($this->proof)('app.reference.settings.publish');
    $this->postJson('/api/app/reference/settings/publish', [...$next, 'review' => $nextReview])->assertOk();
    ($this->grant)(['settings.credential.read']);
    $this->getJson('/api/app/reference/settings')->assertOk()->assertJsonPath('data.settings.credential.set', false)->assertJsonMissingPath('data.settings.credential.value');
});

it('dispatches durable work with its own proof and refuses replay expiry and revoked authority', function (): void {
    ($this->grant)(['work.dispatch', 'work.read']);
    $this->postJson('/api/app/reference/work')->assertForbidden();
    ($this->proof)('runa.admin.enter');
    $this->postJson('/api/app/reference/work')->assertForbidden();
    ($this->proof)('app.reference.work.dispatch');
    $id = $this->postJson('/api/app/reference/work')->assertOk()->assertJsonPath('data.status', 'admitted')->json('data.work_id');
    $this->postJson('/api/app/reference/work')->assertForbidden();
    $this->getJson('/api/app/reference/work')->assertOk()->assertJsonFragment(['work_id' => $id, 'status' => 'pending']);
    ($this->proof)('app.reference.work.dispatch');
    $this->travel(301)->seconds();
    $this->postJson('/api/app/reference/work')->assertForbidden();
    $this->travelBack();
    DB::table('runa_permissions.user_permissions')->where('user_public_id', $this->actorId)->delete();
    $this->postJson('/api/app/reference/work')->assertForbidden();
    $this->getJson('/api/app/reference/work')->assertForbidden();
});

it('rolls back dispatch queue proof and explicit history when Audit refuses', function (): void {
    ($this->grant)(['work.dispatch']);
    ($this->proof)('app.reference.work.dispatch');
    $before = DB::table('shared.module_work')->count();
    $queued = DB::table('shared.jobs')->where('queue', 'reference-example-test')->count();
    DB::statement("ALTER TABLE runa_audit.events ADD CONSTRAINT reference_test_audit_refusal CHECK (module <> 'app.reference') NOT VALID");
    $this->postJson('/api/app/reference/work')->assertStatus(500);
    expect(DB::table('shared.module_work')->count())->toBe($before)
        ->and(DB::table('shared.jobs')->where('queue', 'reference-example-test')->count())->toBe($queued);
    DB::statement('ALTER TABLE runa_audit.events DROP CONSTRAINT reference_test_audit_refusal');
    $this->postJson('/api/app/reference/work')->assertOk(); // Rolled-back proof remains usable.
});

it('loads committed snapshots into values without publishing or copying secrets', function (): void {
    ($this->grant)(['settings.read', 'settings.write']);
    $state = $this->getJson('/api/app/reference/settings')->assertOk()->json('data');
    $input = ['revision' => $state['revision'], 'review_id' => $state['review_id']];
    $response = $this->postJson('/api/app/reference/snapshots', [...$input, 'revision' => $state['revision'] + 1, 'name' => 'Stale']);
    expect($response->status())->toBe(409, $response->getContent());
    $this->postJson('/api/app/reference/snapshots', [...$input, 'name' => 'Committed label'])->assertOk()->assertJsonPath('data.status', 'committed');
    $catalog = $this->getJson('/api/app/reference/snapshots')->assertOk()->json('data');
    $snapshot = json_decode($catalog['snapshots'], true, flags: JSON_THROW_ON_ERROR)[0];
    $loaded = $this->getJson('/api/app/reference/snapshots?public_id='.$snapshot['public_id'])->assertOk()->json('data');
    expect(json_decode($loaded['values'], true, flags: JSON_THROW_ON_ERROR))->toBe(['label' => $state['settings']['label']['value']]);
    $this->getJson('/api/app/reference/settings')->assertOk()->assertJsonPath('data.revision', $state['revision']);
    $this->deleteJson('/api/app/reference/snapshots', [...$input, 'public_id' => $snapshot['public_id']])->assertForbidden();
    ($this->proof)('app.reference.settings.snapshot_delete');
    $this->deleteJson('/api/app/reference/snapshots', [...$input, 'public_id' => $snapshot['public_id']])->assertOk();
    $this->getJson('/api/app/reference/snapshots?public_id='.$snapshot['public_id'])->assertNotFound();
});

it('requires the Audit read grant independently of owning history access', function (): void {
    ($this->grant)(['history.read']);
    $this->getJson('/api/app/reference/history')->assertForbidden();
    ($this->grant)(['runa.audit.read']);
    $this->getJson('/api/app/reference/history')->assertOk();
});

it('rolls settings secret revision and its proof back on required Audit refusal', function (): void {
    ($this->grant)(['settings.read', 'settings.write', 'settings.credential.write']);
    $state = $this->getJson('/api/app/reference/settings')->assertOk()->json('data');
    $input = ['revision' => $state['revision'], 'review_id' => $state['review_id'],
        'values' => ['label' => 'Audited candidate', 'credential' => 'rollback-private-value']];
    $review = $this->postJson('/api/app/reference/settings/review', $input)->assertOk()->json('data.review');
    ($this->proof)('app.reference.settings.publish');
    $transitions = DB::table('shared.module_transitions')->where('target', 'app.reference')->count();
    DB::statement("ALTER TABLE runa_audit.events ADD CONSTRAINT reference_test_audit_refusal CHECK (module <> 'app.reference') NOT VALID");
    $this->postJson('/api/app/reference/settings/publish', [...$input, 'review' => $review])->assertStatus(500);
    $this->getJson('/api/app/reference/settings')->assertOk()->assertJsonPath('data.revision', $state['revision'])
        ->assertJsonPath('data.settings.label.value', $state['settings']['label']['value']);
    expect(DB::table('runa_auth.step_up_proofs')->where('user_id', DB::table('runa_auth.users')->where('public_id', $this->actorId)->value('id'))->where('action', 'app.reference.settings.publish')->whereNotNull('consumed_at')->exists())->toBeFalse();
    expect(DB::table('shared.module_transitions')->where('target', 'app.reference')->count())->toBe($transitions);
    DB::statement('ALTER TABLE runa_audit.events DROP CONSTRAINT reference_test_audit_refusal');
    $this->postJson('/api/app/reference/settings/publish', [...$input, 'review' => $review])->assertOk();
    // A successful intervening publication invalidates the original reviewed patch.
    ($this->proof)('app.reference.settings.publish');
    $this->postJson('/api/app/reference/settings/publish', [...$input, 'review' => $review])->assertConflict();
});
