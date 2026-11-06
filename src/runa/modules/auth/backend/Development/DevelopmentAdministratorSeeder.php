<?php

namespace Runa\Modules\Auth\Development;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Hash;
use Runa\Modules\Auth\Contracts\OperatorAuthority;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Support\EntryTransaction;
use Runa\Support\Seeding\DevelopmentFixtures;
use Runa\Support\Seeding\ManagedSeeder;
use Runa\Support\Seeding\SeedCategory;
use Runa\Support\Seeding\SeedContext;

/** Owner-approved initial-password exception for the configured development fixture only. */
final class DevelopmentAdministratorSeeder extends ManagedSeeder
{
    public static function category(): SeedCategory
    {
        return SeedCategory::Development;
    }

    public static function dependsOn(): array
    {
        return [AuthDevelopmentSeeder::class];
    }

    protected function seed(SeedContext $context): void
    {
        $email = config('seeding.development.'.AuthDevelopmentSeeder::class.'.administrator_email');
        $password = config('seeding.development.'.AuthDevelopmentSeeder::class.'.administrator_password');
        if (($email === null || $email === '') && ($password === null || $password === '')) {
            return;
        }
        if (! is_string($email) || $email === '' || ! is_string($password) || strlen($password) < 8 || strlen($password) > 72 || str_contains($password, "\0")) {
            throw new \RuntimeException('Configure a development administrator email and an 8–72 byte initial password.');
        }
        app(DevelopmentFixtures::class)->run('runa.auth', function (Connection $connection) use ($email, $password): void {
            $subject = $connection->table('runa_auth.development_fixtures')->where('scenario', 'administrator')->value('subject');
            $user = User::where('public_id', $subject)->lockForUpdate()->firstOrFail();
            if ($user->password !== null) {
                return;
            }
            if ($user->email !== $email || $user->deleted_at !== null || ! $user->active
                || $connection->table('runa_auth.initialization')->where('id', 1)->value('subject') !== $subject
                || ! app(OperatorAuthority::class)->isAdministrator($connection, $user->public_id)) {
                throw new \RuntimeException('Configured development administrator does not match the owned operator fixture.');
            }
            // Direct initial fixture provisioning: ordinary password mutations keep their full policy.
            $user->password = Hash::make($password);
            $user->password_changed_at = now('UTC');
            $user->email_verified_at = now('UTC');
            $user->password_change_required = false;
            $user->operator_recovery_pending = false;
            $user->security_version++;
            $user->version++;
            $user->save();
            $connection->table('runa_auth.operator_tokens')->where('user_id', $user->id)->whereNull('consumed_at')->update(['consumed_at' => now('UTC')]);
            app(EntryTransaction::class)->record('development.password_initialized', $user);
        });
    }
}
