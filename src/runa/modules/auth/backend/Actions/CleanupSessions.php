<?php

namespace Runa\Modules\Auth\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Database\Connection;
use Runa\Modules\Auth\Support\EntryTransaction;
use Runa\Support\Modules\Settings\ModuleSettings;

/** Bounded technical deletion only. Audit is never in the cleanup target set. */
final readonly class CleanupSessions
{
    public function __construct(private EntryTransaction $transactions, private ModuleSettings $settings) {}

    public function run(): void
    {
        $this->transactions->run(function (Connection $connection): void {
            $this->transactions->lockAuthority($connection);
            $now = CarbonImmutable::now('UTC');
            $proofIds = $connection->table('runa_auth.step_up_proofs')->where('expires_at', '<=', $now)->orderBy('id')->limit(500)->pluck('id');
            $connection->table('runa_auth.step_up_proofs')->whereIn('id', $proofIds)->delete();
            foreach (['sessions', 'trusted_devices', 'login_windows'] as $table) {
                $query = $connection->table('runa_auth.'.$table);
                $query->where(function ($query) use ($table, $now): void {
                    $query->where('expires_at', '<=', $now);
                    if ($table !== 'login_windows') {
                        $query->orWhereNotNull('revoked_at');
                        $query->orWhereExists(function ($users) use ($table): void {
                            $users->selectRaw('1')->from('runa_auth.users')
                                ->whereColumn('runa_auth.users.id', 'runa_auth.'.$table.'.user_id')
                                ->whereColumn('runa_auth.users.security_version', '<>', 'runa_auth.'.$table.'.security_version');
                        });
                    }
                    if ($table === 'sessions') {
                        $query->orWhere('activity_at', '<=', $now->subMinutes($this->settings->value('runa.auth', 'session_idle_minutes')));
                    }
                });
                $ids = $query->orderBy('id')->limit(500)->pluck('id');
                $connection->table('runa_auth.'.$table)->whereIn('id', $ids)->delete();
            }
        });
    }
}
