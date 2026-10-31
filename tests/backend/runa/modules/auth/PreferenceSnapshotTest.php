<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\ModuleFramework\Updates\ModuleUpdates;
use Runa\Modules\Auth\Actions\BulkSessions;
use Runa\Modules\Auth\Actions\ChangeIdentity;
use Runa\Modules\Auth\Actions\DeliveryRouting;
use Runa\Modules\Auth\Actions\SecurityNotices;
use Runa\Modules\Auth\Actions\StepUp;
use Runa\Modules\Auth\Contracts\BulkUserSelection;
use Runa\Modules\Auth\Contracts\NewIdentity;
use Runa\Modules\Auth\Contracts\PreferenceScope;
use Runa\Modules\Auth\Contracts\SessionSelection;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Queries\AccountPreferenceSnapshot;
use Runa\Modules\Auth\Support\EntryTransaction;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Settings\ModuleSettings;
use Tests\Fixtures\CoreModuleStorage;
use Tests\PlatformTestCase;

uses(PlatformTestCase::class);

beforeEach(function (): void {
    foreach (['auth', 'audit', 'permissions'] as $code) {
        if (DB::selectOne('SELECT 1 FROM pg_namespace WHERE nspname = ?', ['runa_'.$code])) {
            throw new RuntimeException('Preference fixture requires absent disposable Core schemas.');
        }
    }
    app(ModuleUpdates::class)->apply('runa', str_repeat('e', 40), static function (): void {});
    config()->set('queue.default', 'database');
});

afterEach(function (): void {
    while (DB::transactionLevel() > 0) {
        DB::rollBack();
    }
    $authId = DB::table('shared.modules')->where('module_id', 'runa.auth')->value('id');
    foreach (DB::table('shared.module_work')->where('module_id', $authId)->pluck('public_id') as $id) {
        DB::table('shared.jobs')->whereRaw("payload::jsonb ->> 'runa_work' = ?", [$id])->delete();
    }
    DB::table('shared.module_work')->where('module_id', $authId)->delete();
    CoreModuleStorage::removePermissions();
    foreach (['auth', 'audit'] as $code) {
        foreach (CoreModuleStorage::TABLES[$code] as $table) {
            DB::statement('DROP TABLE IF EXISTS runa_'.$code.'.'.$table.' RESTRICT');
        }
        DB::statement('DROP SCHEMA IF EXISTS runa_'.$code.' RESTRICT');
        CoreModuleStorage::removeTransitions('runa.'.$code);
        $id = DB::table('shared.modules')->where('module_id', 'runa.'.$code)->value('id');
        DB::table('shared.module_update_steps')->where('module_id', $id)->delete();
        DB::table('shared.modules')->where('id', $id)->delete();
    }
    DB::table('shared.module_registry_state')->where('id', 1)->increment('generation');
});

it('batches reminder history and current policy exceptions across the locked account cohort', function (): void {
    $settings = app(ModuleSettings::class);
    $state = $settings->read('runa.auth');
    $settings->update('runa.auth', ['notice_account_expiry' => true, 'email_verification' => 'disabled'], $state['revision'], $state['generation']);
    for ($i = 0; $i < 4; $i++) {
        $identity = app(ChangeIdentity::class)->create(new NewIdentity('reminder-growth-'.$i.'@example.test', name: 'Reminder User', active: true, approved: true));
        DB::transaction(function () use ($identity): void {
            $user = User::where('public_id', $identity->publicId)->firstOrFail();
            $user->expires_at = now()->addDays(2);
            $user->save();
        });
    }
    $db = DB::connection();
    $db->flushQueryLog();
    $db->enableQueryLog();
    try {
        app(SecurityNotices::class)->reminders();
    } finally {
        $queries = $db->getQueryLog();
        $db->disableQueryLog();
    }
    $aggregates = array_filter($queries, static fn (array $query): bool => str_contains($query['query'], 'count(*)') && str_contains($query['query'], '"runa_auth"."security_notices"'));
    // Work dispatch still rechecks each notice's owning authorization. Count
    // the four-account deadline read separately from those security boundaries.
    $exceptions = array_filter($queries, static fn (array $query): bool => str_contains($query['query'], '"runa_auth"."policy_exceptions"') && str_contains($query['query'], 'in (?, ?, ?, ?)'));
    expect($aggregates)->toHaveCount(1)->and($exceptions)->toHaveCount(1)
        ->and($db->table('runa_auth.security_notices')->where('category', 'account_expiry')->count())->toBe(4);
    app(SecurityNotices::class)->reminders();
    expect($db->table('runa_auth.security_notices')->where('category', 'account_expiry')->count())->toBe(4);
});

it('keeps session preview reads constant as the selected account collection grows', function (): void {
    $users = [];
    $db = DB::connection();
    $db->beginTransaction();
    for ($i = 0; $i < 4; $i++) {
        $identity = app(ChangeIdentity::class)->create(new NewIdentity('bulk-growth-'.$i.'@example.test', name: 'Batch User', active: true, approved: true));
        $users[] = $user = User::where('public_id', $identity->publicId)->firstOrFail();
        $db->table('runa_auth.sessions')->insert(['public_id' => (string) Str::uuid7(), 'user_id' => $user->id,
            'session_hash' => hash('sha256', 'batch-'.$i), 'security_version' => $user->security_version, 'remembered' => false,
            'authenticated_at' => now(), 'activity_at' => now(), 'expires_at' => now()->addHour()]);
    }
    $action = new BulkSessions(app(EntryTransaction::class), app(StepUp::class), new class implements BulkUserSelection
    {
        public function authorize(SessionSelection $selection): void {}
    });
    foreach ([1, 4] as $count) {
        $db->flushQueryLog();
        $db->enableQueryLog();
        try {
            $result = $action->preview(new SessionSelection(array_map(static fn (User $user): string => $user->public_id, array_slice($users, 0, $count))), true);
        } finally {
            $queries = $db->getQueryLog();
            $db->disableQueryLog();
        }
        expect($result['accounts'])->toHaveCount($count)->and($result['sessions'])->toBe($count);
        foreach (['sessions', 'trusted_devices'] as $table) {
            expect(count(array_filter($queries, static fn (array $query): bool => str_contains($query['query'], '"runa_auth"."'.$table.'"'))))->toBe(1);
        }
    }
    $old = $result['fingerprint'];
    $db->table('runa_auth.sessions')->where('user_id', $users[0]->id)->update(['revoked_at' => now()]);
    $next = $action->preview(new SessionSelection(array_map(static fn (User $user): string => $user->public_id, $users)), true);
    expect($next['fingerprint'])->not->toBe($old)->and($next['sessions'])->toBe(3);
});

it('loads growing preference snapshots with one query while preserving defaults migration and account isolation', function (): void {
    $identity = app(ChangeIdentity::class)->create(new NewIdentity('snapshot@example.test', name: 'Snapshot User', active: true, approved: true));
    $otherIdentity = app(ChangeIdentity::class)->create(new NewIdentity('snapshot-other@example.test', name: 'Other User', active: true, approved: true));
    $user = User::where('public_id', $identity->publicId)->firstOrFail();
    $other = User::where('public_id', $otherIdentity->publicId)->firstOrFail();
    $db = DB::connection();
    $db->beginTransaction();
    foreach ([[$user->id, 'global:locale', 'en', 1], [$other->id, 'global:locale', 'pl', 1],
        [$user->id, 'global:theme', 'dark', 2], [$user->id, 'navigation:collapsed', 'invalid', 1]] as [$id, $key, $value, $version]) {
        $db->table('runa_auth.preferences')->insert(['user_id' => $id, 'scope' => 'runa:runa.auth:'.$key,
            'module' => 'runa.auth', 'value' => json_encode($value, JSON_THROW_ON_ERROR), 'definition_version' => $version, 'created_at' => now(), 'updated_at' => now()]);
    }
    $snapshot = app(AccountPreferenceSnapshot::class);
    app(ModuleRegistry::class)->snapshot();
    $counts = [];
    foreach (['runa', 'custom-app'] as $application) {
        config()->set('branding.application_id', $application);
        $db->flushQueryLog();
        $db->enableQueryLog();
        try {
            $result = $snapshot->all($db, $user, $application);
        } finally {
            $queries = $db->getQueryLog();
            $db->disableQueryLog();
        }
        $counts[] = count(array_filter($queries, static fn (array $query): bool => str_contains($query['query'], '"runa_auth"."preferences"')));
        expect($result['values'])->toHaveCount($application === 'runa' ? 4 : 8);
        foreach ($result['values'] as $value) {
            [$app, $module, $view, $key] = explode(':', $value['scope']);
            expect($snapshot->one($db, $user, new PreferenceScope($app, $module, $view, $key)))->toBe($value);
        }
        $values = array_column($result['values'], 'value', 'scope');
        expect($values['runa:runa.auth:global:locale'])->toBe('en')
            ->and($values['runa:runa.auth:global:theme'])->toBe('system')
            ->and($values['runa:runa.auth:navigation:collapsed'])->toBeFalse();
        if ($application !== 'runa') {
            expect($values['custom-app:runa.auth:global:locale'])->toBe('pl');
        }
    }
    expect($counts)->toBe([1, 1]);
    $db->table('runa_auth.delivery_preferences')->insert([
        ['user_id' => $user->id, 'module' => 'runa.auth', 'category' => 'new_browser', 'enabled' => false, 'recipients' => '["primary"]', 'created_at' => now(), 'updated_at' => now()],
        ['user_id' => $other->id, 'module' => 'runa.auth', 'category' => 'new_browser', 'enabled' => true, 'recipients' => '["primary"]', 'created_at' => now(), 'updated_at' => now()],
        ['user_id' => $user->id, 'module' => 'runa.auth', 'category' => 'locked', 'enabled' => false, 'recipients' => '["primary"]', 'created_at' => now(), 'updated_at' => now()],
    ]);
    $db->flushQueryLog();
    $db->enableQueryLog();
    try {
        $delivery = app(DeliveryRouting::class)->own($db, $user);
    } finally {
        $queries = $db->getQueryLog();
        $db->disableQueryLog();
    }
    expect(count(array_filter($queries, static fn (array $query): bool => str_contains($query['query'], '"runa_auth"."delivery_preferences"'))))->toBe(1);
    $categories = array_column($delivery['categories'], null, 'key');
    expect(count($categories))->toBeGreaterThan(1)
        ->and($categories['new_browser']['enabled'])->toBeFalse()
        ->and($categories['locked']['enabled'])->toBeTrue()
        ->and($categories['locked']['recipients'])->toBe(['primary']);
    $db->table('runa_auth.preferences')->where('user_id', $user->id)->where('scope', 'runa:runa.auth:global:locale')->update(['value' => '"pl"']);
    expect(array_column($snapshot->all($db, $user, 'runa')['values'], 'value', 'scope')['runa:runa.auth:global:locale'])->toBe('pl');
    expect(fn () => $snapshot->all($db, $user, 'unknown-app'))->toThrow(OperationRejected::class);
});
