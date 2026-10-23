<?php

namespace Runa\Modules\Auth\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Support\EntryTransaction;
use Runa\Support\Modules\Settings\ModuleSettings;

/** Serialized inside EntryTransaction. No raw source address or login identifier is retained. */
final readonly class LoginAbuse
{
    public function __construct(private ModuleSettings $settings, private EntryTransaction $transactions) {}

    public function source(Connection $connection, Request $request): bool
    {
        return $this->count($connection, 'source:'.(string) $request->ip(), $this->settings->value('runa.auth', 'abuse_source_limit'));
    }

    public function blocked(Connection $connection, string $identifier): bool
    {
        $row = $connection->table('runa_auth.login_windows')->where('fingerprint', $this->key('account:'.$identifier))->first();

        return $row !== null && $row->locked_until !== null && CarbonImmutable::now('UTC')->lt(CarbonImmutable::parse($row->locked_until));
    }

    public function failure(Connection $connection, string $identifier, ?User $user): void
    {
        $this->count($connection, 'account:'.$identifier, $this->settings->value('runa.auth', 'abuse_account_limit'), $user);
    }

    private function count(Connection $connection, string $subject, int $limit, ?User $user = null): bool
    {
        $now = CarbonImmutable::now('UTC');
        $key = $this->key($subject);
        $row = $connection->table('runa_auth.login_windows')->where('fingerprint', $key)->first();
        if ($row !== null && $row->locked_until !== null && $now->lt(CarbonImmutable::parse($row->locked_until))) {
            return false; // Blocked traffic never extends a lock.
        }
        $attempts = $row === null || $now->gte(CarbonImmutable::parse($row->expires_at)) ? 1 : $row->attempts + 1;
        $expiry = $row === null || $now->gte(CarbonImmutable::parse($row->expires_at)) ? $now->addSeconds($this->settings->value('runa.auth', 'abuse_window_seconds')) : CarbonImmutable::parse($row->expires_at);
        $locked = $attempts >= $limit ? $now->addSeconds($this->settings->value('runa.auth', 'abuse_lock_seconds')) : null;
        $connection->table('runa_auth.login_windows')->updateOrInsert(['fingerprint' => $key], [
            'attempts' => $attempts, 'expires_at' => $locked === null ? $expiry : $expiry->max($locked), 'locked_until' => $locked,
        ]);
        $escalation = $this->settings->value('runa.auth', 'abuse_escalation_limit');
        if ($user !== null && $escalation > 0 && $attempts === $escalation) {
            // Escalate for authorized review, never an attacker-controlled permanent account lock.
            $this->transactions->record('login.abuse_escalated', $user);
        }

        return $attempts <= $limit;
    }

    public function identifier(Request $request, ?User $user): string
    {
        return $user?->public_id ?? strtolower(trim($request->string('identifier')->toString()));
    }

    private function key(string $subject): string
    {
        return hash_hmac('sha256', $subject, config('app.key'));
    }
}
