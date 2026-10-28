<?php

namespace Runa\Modules\Auth\Development;

use Illuminate\Database\Connection;
use Illuminate\Support\Collection;
use Runa\Modules\Auth\Actions\ChangeIdentity;
use Runa\Modules\Auth\Actions\OperatorAccounts;
use Runa\Modules\Auth\Actions\Passwords;
use Runa\Modules\Auth\Actions\SessionStore;
use Runa\Modules\Auth\Contracts\DevelopmentAccounts;
use Runa\Modules\Auth\Contracts\LifecycleChange;
use Runa\Modules\Auth\Contracts\NewIdentity;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Support\EntryTransaction;
use Runa\Support\Seeding\DevelopmentContributor;
use Runa\Support\Seeding\DevelopmentFixtures;
use Runa\Support\Seeding\SeedContext;

/** Auth-owned scenarios and private initial credentials. No foreign persistence. */
final class Accounts implements DevelopmentAccounts, DevelopmentContributor
{
    public function dependsOn(): array
    {
        return [];
    }

    public function seeders(): array
    {
        return [AuthDevelopmentSeeder::class];
    }

    public function populate(): void
    {
        app(DevelopmentFixtures::class)->run('runa.auth', function (Connection $c): void {
            $context = new SeedContext(config()->array('seeding.development.'.AuthDevelopmentSeeder::class, []));
            $count = $context->count('extra_users', 3);
            if ($count > 100) {
                throw new \InvalidArgumentException('Auth development extra_users limit is 100.');
            }
            $scenarios = ['administrator', 'reader', 'direct', 'pending', 'unverified', 'password_change', 'second_factor', 'sessions', 'inactive', 'locked', 'expired', 'scheduled'];
            for ($index = 1; $index <= $count; $index++) {
                $scenarios[] = 'extra_'.$index;
            }
            foreach ($scenarios as $scenario) {
                // Draw even for existing scenarios so incremental reruns retain independent streams.
                $number = $context->random->getInt(1000, 9999);
                if ($c->table('runa_auth.development_fixtures')->where('scenario', $scenario)->exists()) {
                    continue;
                }
                $input = new NewIdentity('dev.'.$scenario.'@example.test', name: 'Development '.str_replace('_', ' ', $scenario).' '.$number,
                    login: 'dev.'.$scenario, active: ! in_array($scenario, ['inactive', 'scheduled'], true), approved: $scenario !== 'pending');
                if ($scenario === 'administrator') {
                    // Existing non-fixture administrators/identifiers refuse; never adopt or regrant them.
                    if ($c->table('runa_auth.initialization')->where('id', 1)->value('completed_at') !== null) {
                        throw new \RuntimeException('Administrator initialization is already owned outside this fixture.');
                    }
                    $material = app(OperatorAccounts::class)->initialize($input);
                    $user = User::where('public_id', $material['public_id'])->lockForUpdate()->firstOrFail();
                    unset($material);
                } else {
                    $identity = app(ChangeIdentity::class)->create($input);
                    $user = User::where('public_id', $identity->publicId)->lockForUpdate()->firstOrFail();
                }
                if (! in_array($scenario, ['administrator', 'unverified'], true)) {
                    $user->email_verified_at = now('UTC');
                }
                if ($scenario === 'second_factor') {
                    $user->email_factor = true;
                }
                $user->save();
                $change = match ($scenario) {
                    'password_change' => new LifecycleChange('require_password_change', enabled: true),
                    'locked' => new LifecycleChange('set_security_lock', enabled: true),
                    'expired' => new LifecycleChange('set_expiry', at: now('UTC')->subDay()->format('Y-m-d\TH:i:s\Z')),
                    'scheduled' => new LifecycleChange('schedule_activation', at: now('UTC')->addDay()->format('Y-m-d\TH:i:s\Z')),
                    default => null,
                };
                if ($change !== null) {
                    app(ChangeIdentity::class)->change($user->public_id, $user->version, $change);
                }
                if ($scenario === 'sessions') {
                    // Real tracked rows with undistributed random browser secrets; no reusable session issued.
                    app(SessionStore::class)->admit($c, $user, bin2hex(random_bytes(32)), false);
                    app(EntryTransaction::class)->record('development.sessions_created', $user);
                }
                $c->table('runa_auth.development_fixtures')->insert(['scenario' => $scenario, 'subject' => $user->public_id, 'created_at' => now('UTC')]);
                app(EntryTransaction::class)->record('development.account_created', $user);
            }
        });
    }

    public function accounts(): array
    {
        return app(DevelopmentFixtures::class)->run('runa.auth', function (Connection $c): array {
            /** @var Collection<int, \stdClass&object{scenario: string, public_id: string, email: string, login: string|null, deleted_at: string|null, setup_required: bool}> $rows */
            $rows = $c->table('runa_auth.development_fixtures as f')->join('runa_auth.users as u', 'u.public_id', '=', 'f.subject')
                ->orderBy('f.scenario')->select(['f.scenario', 'u.public_id', 'u.email', 'u.login', 'u.deleted_at'])
                ->selectRaw('u.password IS NULL AS setup_required')->get();

            return array_values($rows->map(fn ($row): array => ['scenario' => $row->scenario, 'public_id' => $row->public_id,
                'email' => $row->email, 'login' => $row->login, 'setup_required' => (bool) $row->setup_required,
                'deleted' => $row->deleted_at !== null])->all());
        });
    }

    public function setup(string $scenario, #[\SensitiveParameter] string $password): bool
    {
        return app(DevelopmentFixtures::class)->run('runa.auth', function (Connection $c) use ($scenario, $password): bool {
            $subject = $c->table('runa_auth.development_fixtures')->where('scenario', $scenario)->value('subject')
                ?? throw new \RuntimeException('Run the module development corpus first.');
            $user = User::where('public_id', $subject)->lockForUpdate()->firstOrFail();
            if ($user->password !== null) {
                return false;
            }
            if ($scenario === 'administrator') {
                $input = new NewIdentity($user->email, name: $user->name, login: $user->login);
                $material = app(OperatorAccounts::class)->initialize($input);
                app(OperatorAccounts::class)->redeem($material['token'], $password);
            } else {
                $forcedChange = $user->password_change_required;
                app(Passwords::class)->replaceLocked($c, $user, $password);
                $user->password_change_required = $forcedChange;
                $user->save();
            }
            app(EntryTransaction::class)->record('development.password_initialized', $user);

            return true;
        });
    }
}
