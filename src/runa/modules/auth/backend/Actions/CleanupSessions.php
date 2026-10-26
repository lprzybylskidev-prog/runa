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
            $noticeIds = $connection->table('runa_auth.security_notices')->where('expires_at', '<=', $now->subDays(30))->orderBy('id')->limit(500)->pluck('id');
            $connection->table('runa_auth.security_notices')->whereIn('id', $noticeIds)->delete();
            $operatorIds = $connection->table('runa_auth.operator_tokens')->where('expires_at', '<=', $now)->orderBy('id')->limit(500)->pluck('id');
            $connection->table('runa_auth.operator_tokens')->whereIn('id', $operatorIds)->delete();
            $proofIds = $connection->table('runa_auth.step_up_proofs')->where('expires_at', '<=', $now)->orderBy('id')->limit(500)->pluck('id');
            $connection->table('runa_auth.step_up_proofs')->whereIn('id', $proofIds)->delete();
            $mailIds = $connection->table('runa_auth.profile_mail')->where('expires_at', '<=', $now)->orderBy('id')->limit(500)->pluck('id');
            $connection->table('runa_auth.profile_mail')->whereIn('id', $mailIds)->delete();
            $changeIds = $connection->table('runa_auth.email_changes')->where('expires_at', '<=', $now)->whereNotExists(fn ($q) => $q->selectRaw('1')->from('runa_auth.profile_mail')->whereColumn('change_id', 'runa_auth.email_changes.id'))->orderBy('id')->limit(500)->pluck('id');
            $connection->table('runa_auth.email_changes')->whereIn('id', $changeIds)->delete();
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
                        // The correlated owned exception retains valid extended sessions; all inputs are bound.
                        $revision = app('db')->connection()->table('shared.modules')->where('module_id', 'runa.auth')->value('settings_revision');
                        $query->orWhereRaw("activity_at <= ?::timestamptz - make_interval(mins => COALESCE((SELECT value FROM runa_auth.policy_exceptions e WHERE e.user_id = runa_auth.sessions.user_id AND e.key = 'session_idle_minutes' AND e.revoked_at IS NULL AND e.expires_at > ?::timestamptz AND e.policy_revision = ?), ?))", [$now, $now, $revision, $this->settings->value('runa.auth', 'session_idle_minutes')]);
                    }
                });
                $ids = $query->orderBy('id')->limit(500)->pluck('id');
                $connection->table('runa_auth.'.$table)->whereIn('id', $ids)->delete();
            }
        });
        app(Avatars::class)->cleanup();
    }
}
