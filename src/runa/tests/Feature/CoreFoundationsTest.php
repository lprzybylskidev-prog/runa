<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\AuthorityGuard;
use Runa\Contracts\Modules\DictionaryEntry;
use Runa\Contracts\Modules\HttpAccess;
use Runa\Contracts\Modules\HttpAccountPolicy;
use Runa\Contracts\Modules\ManagementEntity;
use Runa\Contracts\Modules\ManagementField;
use Runa\Contracts\Modules\ManagementOperation;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Api\PrincipalScope;
use Runa\Support\Modules\Data\AuthorityLock;
use Tests\Fixtures\CoreModuleStorage;

it('unwinds trusted principal and recursive resolution across failed and sequential roots', function (): void {
    $scope = app(PrincipalScope::class);
    $principal = new TrustedPrincipal((string) Str::uuid7());
    expect(fn () => $scope->run($principal, function () use ($scope, $principal): void {
        expect($scope->current())->toBe($principal);
        $scope->run(null, fn () => expect($scope->current())->toBeNull());
        expect($scope->current())->toBe($principal);
        $scope->resolve(fn () => $scope->resolve(fn () => true));
    }))->toThrow(RuntimeException::class, 'Recursive identity');
    expect($scope->current())->toBeNull();
    expect($scope->resolve(fn () => true))->toBeTrue();
    expect(fn () => new TrustedPrincipal('request-controlled-name'))->toThrow(InvalidArgumentException::class);
});

it('denies authenticated restricted and authority operations before domain integration', function (): void {
    $policy = app(HttpAccountPolicy::class);
    expect($policy->principal(Request::create('/'), 'app.example', 'read', HttpAccess::Public))->toBeNull();
    foreach ([HttpAccess::Authenticated, HttpAccess::Restricted] as $access) {
        expect(fn () => $policy->principal(Request::create('/'), 'app.example', 'read', $access))->toThrow(OperationRejected::class);
    }
    expect(fn () => DB::transaction(fn () => app(AuthorityGuard::class)->assertMayRemove(DB::connection(), [(string) Str::uuid7()])))->toThrow(RuntimeException::class, 'unavailable');
    expect(fn () => app(AuthorityLock::class)->acquire(DB::connection()))->toThrow(RuntimeException::class, 'active PostgreSQL transaction');
});

it('bounds entity metadata and projects database dictionary columns without executable paths', function (): void {
    $names = ['pl' => 'Nazwa', 'en' => 'Name'];
    $descriptions = ['pl' => 'Opis', 'en' => 'Description'];
    $dictionary = DictionaryEntry::fromLocalized('account.active', $names, $descriptions);
    expect(array_keys($dictionary->toRow()))->toBe(['code', 'name_pl', 'name_en', 'description_pl', 'description_en']);
    $field = new ManagementField('name', 'text', $names, $descriptions, writable: true);
    $operation = new ManagementOperation(new ApiOperation('update', 'account.update'), ['name'], 'both');
    $entity = new ManagementEntity('account', 'account.read', $names, $descriptions, [$field], [$operation]);
    expect($entity->schemaVersion)->toBe(1)->and($entity->operations[0]->revisionRequired)->toBeTrue();
    expect(fn () => new ManagementEntity('account', 'account.read', $names, $descriptions, [], [$operation]))->toThrow(InvalidArgumentException::class);
    expect(fn () => new ManagementEntity('account', 'account.read', $names, $descriptions, [$field, $field], []))->toThrow(InvalidArgumentException::class);
    expect(fn () => new ManagementField('secret', 'text', $names, $descriptions, filterable: true, sensitive: true))->toThrow(InvalidArgumentException::class);
    expect(fn () => new ManagementField('name', 'text', $names, $descriptions, required: true))->toThrow(InvalidArgumentException::class);
    expect(fn () => new ManagementField('name', 'text', $names, $descriptions, choices: ['allowed', 'allowed']))->toThrow(InvalidArgumentException::class);
    expect(fn () => new ManagementField('name', 'text', $names, $descriptions, multiple: true))->toThrow(InvalidArgumentException::class);
    expect(fn () => new ManagementOperation(new ApiOperation('update'), []))->toThrow(InvalidArgumentException::class);
    expect(fn () => new ManagementField('owner', 'uuid', $names, $descriptions, referenceModule: 'runa.auth'))->toThrow(InvalidArgumentException::class);
});

it('holds the shared authority lock until transaction completion across connections', function (): void {
    config(['database.connections.authority_probe' => config('database.connections.pgsql')]);
    $other = DB::connection('authority_probe');
    $connection = DB::connection();
    try {
        $connection->beginTransaction();
        app(AuthorityLock::class)->acquire($connection);
        expect($other->selectOne('SELECT pg_try_advisory_xact_lock(1381322305, 11) AS acquired')->acquired)->toBeFalse();
        $connection->rollBack();
        expect($other->selectOne('SELECT pg_try_advisory_xact_lock(1381322305, 11) AS acquired')->acquired)->toBeTrue();
    } finally {
        if ($connection->transactionLevel() > 0) {
            $connection->rollBack();
        }
        DB::purge('authority_probe');
    }
});

it('cleans only the selected Core fixture history and refuses foreign module ownership', function (): void {
    $ids = [(string) Str::uuid7(), (string) Str::uuid7(), (string) Str::uuid7()];
    try {
        foreach (['runa.auth', 'runa.audit', 'app.cleanup_fixture'] as $index => $target) {
            DB::table('shared.module_transitions')->insert([
                'public_id' => $ids[$index], 'intent' => 'settings', 'target' => $target,
                'generation' => 0, 'affected' => '[]', 'payload' => 'fixture-only',
                'status' => 'succeeded', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        expect(fn () => CoreModuleStorage::removeTransitions('app.cleanup_fixture'))
            ->toThrow(InvalidArgumentException::class, 'foreign');
        CoreModuleStorage::removeTransitions('runa.auth');
        expect(DB::table('shared.module_transitions')->where('public_id', $ids[0])->exists())->toBeFalse()
            ->and(DB::table('shared.module_transitions')->where('public_id', $ids[1])->exists())->toBeTrue()
            ->and(DB::table('shared.module_transitions')->where('public_id', $ids[2])->exists())->toBeTrue();
    } finally {
        DB::table('shared.module_transitions')->whereIn('public_id', $ids)->delete();
    }
});
