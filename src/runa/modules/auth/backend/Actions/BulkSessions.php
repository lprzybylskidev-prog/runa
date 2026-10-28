<?php

namespace Runa\Modules\Auth\Actions;

use Illuminate\Database\Connection;
use Runa\Modules\Auth\Contracts\AuthBulkSessions;
use Runa\Modules\Auth\Contracts\BulkUserSelection;
use Runa\Modules\Auth\Contracts\SessionSelection;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Support\EntryTransaction;
use Runa\Support\Modules\Api\OperationRejected;

final readonly class BulkSessions implements AuthBulkSessions
{
    public function __construct(private EntryTransaction $transactions, private StepUp $stepUp, private BulkUserSelection $selection) {}

    public function preview(SessionSelection $selection, bool $trust = false): array
    {
        return $this->transactions->run(function (Connection $connection) use ($selection, $trust): array {
            $this->transactions->lockAuthority($connection);
            $this->selection->authorize($selection);

            return $this->snapshot($connection, $selection, $trust);
        });
    }

    public function revoke(SessionSelection $selection, bool $trust, string $fingerprint): array
    {
        return $this->transactions->run(function (Connection $connection) use ($selection, $trust, $fingerprint): array {
            $this->transactions->lockAuthority($connection);
            $this->selection->authorize($selection);
            $this->stepUp->assertVerified('runa.auth.sessions.bulk_revoke', 'both', everyAction: true);
            $preview = $this->snapshot($connection, $selection, $trust);
            if (! hash_equals($preview['fingerprint'], $fingerprint)) {
                throw new OperationRejected('session_preview_stale', 'runa.auth', 'sessions.bulk_revoke', 409);
            }
            foreach ($preview['accounts'] as $account) {
                $user = User::withTrashed()->where('public_id', $account['public_id'])->firstOrFail();
                $connection->table('runa_auth.sessions')->where('user_id', $user->id)->whereNull('revoked_at')->update(['revoked_at' => now()]);
                if ($trust) {
                    $connection->table('runa_auth.trusted_devices')->where('user_id', $user->id)->whereNull('revoked_at')->update(['revoked_at' => now()]);
                }
                $this->transactions->record('sessions.bulk_revoked', $user);
            }

            return ['accounts' => count($preview['accounts']), 'sessions' => $preview['sessions'], 'trusted_devices' => $preview['trusted_devices']];
        });
    }

    /**
     * @return array{accounts: list<array{public_id: string, version: int, security_version: int, sessions: list<string>, trusted_devices: list<string>}>, sessions: int, trusted_devices: int, fingerprint: string}
     */
    private function snapshot(Connection $connection, SessionSelection $selection, bool $trust): array
    {
        $users = User::withTrashed()->when(! $selection->all, fn ($q) => $q->whereIn('public_id', $selection->publicIds))
            ->orderBy('public_id')->limit(101)->lockForUpdate()->get();
        if ($users->count() > 100 || (! $selection->all && $users->count() !== count($selection->publicIds))) {
            throw new OperationRejected('session_selection_invalid', 'runa.auth', 'sessions.bulk_preview', 422);
        }
        $accounts = [];
        $sessions = $devices = 0;
        foreach ($users as $user) {
            /** @var list<string> $ids */
            $ids = $connection->table('runa_auth.sessions')->where('user_id', $user->id)->whereNull('revoked_at')->orderBy('id')->limit(10001)->pluck('public_id')->all();
            /** @var list<string> $trusted */
            $trusted = $trust ? $connection->table('runa_auth.trusted_devices')->where('user_id', $user->id)->whereNull('revoked_at')->orderBy('id')->limit(10001)->pluck('public_id')->all() : [];
            if ($sessions + $devices + count($ids) + count($trusted) > 10000) {
                throw new OperationRejected('session_selection_limit', 'runa.auth', 'sessions.bulk_preview', 422);
            }
            $accounts[] = ['public_id' => $user->public_id, 'version' => $user->version, 'security_version' => $user->security_version, 'sessions' => $ids, 'trusted_devices' => $trusted];
            $sessions += count($ids);
            $devices += count($trusted);
        }
        $revision = $connection->table('shared.modules')->where('module_id', 'runa.auth')->value('settings_revision');
        $fingerprint = hash('sha256', json_encode([$revision, $selection, $trust, $accounts], JSON_THROW_ON_ERROR));

        return ['accounts' => $accounts, 'sessions' => $sessions, 'trusted_devices' => $devices, 'fingerprint' => $fingerprint];
    }
}
