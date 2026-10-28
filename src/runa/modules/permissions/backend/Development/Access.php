<?php

namespace Runa\Modules\Permissions\Development;

use Illuminate\Database\Connection;
use Illuminate\Support\Str;
use Runa\Contracts\Modules\DictionaryEntry;
use Runa\Modules\Auth\Contracts\DevelopmentAccounts;
use Runa\Modules\Permissions\Contracts\DevelopmentAccess;
use Runa\Modules\Permissions\Support\AccessTransaction;
use Runa\Support\Seeding\DevelopmentContributor;
use Runa\Support\Seeding\DevelopmentFixtures;
use Runa\Support\Seeding\SeedContext;

/** Fixed low-privilege fixture grants. Real administrator setup stays with Auth's operator. */
final class Access implements DevelopmentAccess, DevelopmentContributor
{
    public function dependsOn(): array
    {
        return ['runa.auth'];
    }

    public function seeders(): array
    {
        return [PermissionsDevelopmentSeeder::class];
    }

    public function populate(): void
    {
        app(DevelopmentFixtures::class)->run('runa.permissions', function (Connection $c): void {
            $accounts = app(DevelopmentFixtures::class)->contributor('runa.auth');
            if (! $accounts instanceof DevelopmentAccounts) {
                throw new \RuntimeException('Auth development account contract unavailable.');
            }
            $users = array_column($accounts->accounts(), null, 'scenario');
            foreach (['reader', 'direct'] as $scenario) {
                if (! isset($users[$scenario]) || $users[$scenario]['deleted']) {
                    throw new \RuntimeException('Required development account unavailable.');
                }
            }
            $context = new SeedContext(config()->array('seeding.development.'.PermissionsDevelopmentSeeder::class, []));
            $extra = $context->count('extra_roles', 2);
            if ($extra > 100) {
                throw new \InvalidArgumentException('Permissions development extra_roles limit is 100.');
            }
            $read = $c->table('runa_permissions.permissions')->where('code', 'runa.audit.read')->whereNull('deleted_at')->firstOrFail();
            $inspect = $c->table('runa_permissions.permissions')->where('code', 'runa.permissions.introspection.read')->whereNull('deleted_at')->firstOrFail();
            $role = $this->definition($c, 'role', 'reader', new DictionaryEntry('custom.dev_reader', 'Czytelnik deweloperski', 'Development reader', 'Odczyt historii audytu.', 'Read Audit history.'));
            if ($role['created']) {
                $c->table('runa_permissions.role_permissions')->insert(['role_id' => $role['id'], 'permission_id' => $read->id, 'created_at' => now('UTC'), 'updated_at' => now('UTC')]);
                $this->record('development.role_created', 'role', $role['subject'], ['permissions' => [$read->public_id]]);
            }
            $preset = $this->definition($c, 'preset', 'reader', new DictionaryEntry('custom.dev_reader', 'Zestaw deweloperski', 'Development preset', 'Rola i niezależne uprawnienie.', 'Role and independent direct permission.'));
            if ($preset['created']) {
                $c->table('runa_permissions.preset_roles')->insert(['preset_id' => $preset['id'], 'role_id' => $role['id'], 'created_at' => now('UTC'), 'updated_at' => now('UTC')]);
                $c->table('runa_permissions.preset_permissions')->insert(['preset_id' => $preset['id'], 'permission_id' => $inspect->id, 'created_at' => now('UTC'), 'updated_at' => now('UTC')]);
                $this->record('development.preset_created', 'preset', $preset['subject'], ['roles' => [$role['subject']], 'permissions' => [$inspect->public_id]]);
            }
            foreach (['reader' => ['roles', 'role_id', $role['id'], $role['subject']], 'direct' => ['permissions', 'permission_id', $inspect->id, $inspect->public_id]] as $scenario => [$kind, $column, $id, $reference]) {
                $key = 'assignment.'.$scenario;
                if ($c->table('runa_permissions.development_fixtures')->where('scenario', $key)->exists()) {
                    continue;
                }
                $subject = $users[$scenario]['public_id'];
                $c->table('runa_permissions.user_'.$kind)->insert(['user_public_id' => $subject, $column => $id, 'created_at' => now('UTC'), 'updated_at' => now('UTC')]);
                /** @var int|null $storedRevision */
                $storedRevision = $c->table('runa_permissions.user_access')->where('user_public_id', $subject)->value('version');
                $revision = $storedRevision ?? 0;
                $c->table('runa_permissions.user_access')->updateOrInsert(['user_public_id' => $subject], ['version' => $revision + 1, 'updated_at' => now('UTC')]);
                $this->remember($c, $key, $subject);
                $this->record('development.assignments_created', 'user', $subject, [$kind => [$reference], 'revision' => $revision + 1]);
            }
            for ($index = 1; $index <= $extra; $index++) {
                $number = $context->random->getInt(1000, 9999);
                $entry = $this->definition($c, 'role', 'extra_'.$index, new DictionaryEntry('custom.dev_extra_'.$index, 'Rola '.$number, 'Role '.$number, 'Pusta rola do testów.', 'Empty role for manual testing.'));
                if ($entry['created']) {
                    $this->record('development.role_created', 'role', $entry['subject'], ['permissions' => []]);
                }
            }
        });
    }

    /** @return array{id: int, subject: string, created: bool} */
    private function definition(Connection $c, string $kind, string $key, DictionaryEntry $dictionary): array
    {
        /** @var string|null $subject */
        $subject = $c->table('runa_permissions.development_fixtures')->where('scenario', $kind.'.'.$key)->value('subject');
        $table = 'runa_permissions.'.$kind.'s';
        if ($subject !== null) {
            /** @var \stdClass&object{id: int, deleted_at: string|null} $row */
            $row = $c->table($table)->where('public_id', $subject)->firstOrFail();
            if ($row->deleted_at !== null) {
                throw new \RuntimeException('A retained fixture definition was deleted; it will not be recreated.');
            }

            return ['id' => $row->id, 'subject' => $subject, 'created' => false];
        }
        $subject = (string) Str::uuid7();
        $id = $c->table($table)->insertGetId($dictionary->toRow() + ['public_id' => $subject, 'created_at' => now('UTC'), 'updated_at' => now('UTC')]);
        $this->remember($c, $kind.'.'.$key, $subject);

        return ['id' => $id, 'subject' => $subject, 'created' => true];
    }

    private function remember(Connection $c, string $key, string $subject): void
    {
        $c->table('runa_permissions.development_fixtures')->insert(['scenario' => $key, 'subject' => $subject, 'created_at' => now('UTC')]);
    }

    /** @param array<string, mixed> $after */
    private function record(string $action, string $kind, string $subject, array $after): void
    {
        app(AccessTransaction::class)->record($action, $kind, $subject, null, $after);
    }
}
