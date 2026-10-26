<?php

namespace Runa\Console\Commands;

use Illuminate\Console\Command;
use Runa\Modules\Auth\Contracts\DevelopmentAccounts as AccountContract;
use Runa\Support\Seeding\DevelopmentFixtures;
use Throwable;

final class DevelopmentAccounts extends Command
{
    protected $signature = 'runa:seed:accounts {scenario? : Initialize only this fixture account password} {--setup : Read a new initial password through hidden interactive prompts}';

    protected $description = 'List local fixture account identifiers or privately initialize an unset password; local/testing console only';

    public function handle(DevelopmentFixtures $fixtures): int
    {
        try {
            $accounts = $fixtures->contributor('runa.auth');
            if (! $accounts instanceof AccountContract) {
                throw new \RuntimeException('Auth development contract unavailable.');
            }
            if ($this->option('setup')) {
                $scenario = $this->argument('scenario');
                if (! is_string($scenario) || ! $this->input->isInteractive()) {
                    throw new \RuntimeException('Choose a scenario and use an interactive terminal.');
                }
                $password = $this->secret('Initial password (current Auth policy applies)', false);
                $confirmation = $this->secret('Confirm initial password', false);
                if (! is_string($password) || ! is_string($confirmation) || $password === '' || ! hash_equals($password, $confirmation)) {
                    throw new \RuntimeException('Password confirmation failed.');
                }
                $created = $accounts->setup($scenario, $password);
                unset($password, $confirmation);
                $this->info($created ? 'Initial password saved. Sign in through the normal Auth flow.' : 'Password already exists and was preserved. Use the normal password/recovery flow.');
            } else {
                if ($this->argument('scenario') !== null) {
                    throw new \RuntimeException('A scenario requires --setup.');
                }
                $this->table(['Scenario', 'UUIDv7', 'Email', 'Login', 'Initial setup', 'Deleted'], array_map(fn (array $row): array => [
                    $row['scenario'], $row['public_id'], $row['email'], $row['login'], $row['setup_required'] ? 'required' : 'complete', $row['deleted'] ? 'yes' : 'no',
                ], $accounts->accounts()));
            }

            return self::SUCCESS;
        } catch (Throwable) {
            $this->error('Development accounts rejected. Check local installation, module readiness, seeded scenario, interactive confirmation and current password policy.');

            return self::FAILURE;
        }
    }
}
