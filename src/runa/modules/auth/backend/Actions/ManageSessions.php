<?php

namespace Runa\Modules\Auth\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\Modules\Auth\Contracts\AuthSessions;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Support\EntryTransaction;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Settings\ModuleSettings;

final readonly class ManageSessions implements AuthSessions
{
    public function __construct(private EntryTransaction $transactions, private SessionStore $sessions, private ModuleSettings $settings) {}

    public function listFor(string $publicId, ?string $before = null): array
    {
        new TrustedPrincipal($publicId);
        if ($before !== null) {
            new TrustedPrincipal($before);
        }
        $user = User::withTrashed()->where('public_id', $publicId)->first();
        if ($user === null) {
            return [];
        }

        return $this->sessions->liveRows(app('db')->connection(), $user)
            ->when($before !== null, fn ($query) => $query->where('public_id', '<', $before))
            ->orderByDesc('public_id')->limit(100)->get()
            ->map(fn ($row) => $this->sessions->projection($row))->values()->all();
    }

    public function revoke(string $publicId, string $sessionId): bool
    {
        new TrustedPrincipal($sessionId);

        return $this->change($publicId, $sessionId);
    }

    public function revokeAll(string $publicId): bool
    {
        return $this->change($publicId, null);
    }

    private function change(string $publicId, ?string $sessionId): bool
    {
        new TrustedPrincipal($publicId);

        return $this->transactions->run(function (Connection $connection) use ($publicId, $sessionId): bool {
            $this->transactions->lockAuthority($connection);
            $user = User::withTrashed()->where('public_id', $publicId)->lockForUpdate()->first();
            if ($user === null) {
                return false;
            }
            $query = $connection->table('runa_auth.sessions')->where('user_id', $user->id)->whereNull('revoked_at');
            if ($sessionId !== null) {
                $query->where('public_id', $sessionId);
            }
            $changed = $query->update(['revoked_at' => CarbonImmutable::now('UTC')]) > 0;
            if ($changed) {
                $this->transactions->record($sessionId === null ? 'sessions.revoked' : 'session.revoked', $user);
            }

            return $changed;
        });
    }

    public function activity(Request $request, User $subject): array
    {
        return $this->transactions->run(function (Connection $connection) use ($request, $subject): array {
            $this->transactions->lockAuthority($connection);
            $user = User::where('id', $subject->id)->lockForUpdate()->firstOrFail();
            $row = $this->sessions->current($request, $user);
            if ($row === null) {
                throw new OperationRejected('authentication_required', 'runa.auth', 'session.activity', 401);
            }
            $now = CarbonImmutable::now('UTC');
            if ($this->settings->value('runa.auth', 'session_extension')
                && $now->gte(CarbonImmutable::parse($row->activity_at)->addSeconds($this->settings->value('runa.auth', 'session_heartbeat_seconds')))) {
                $connection->table('runa_auth.sessions')->where('id', $row->id)->update(['activity_at' => $now]);
                $row->activity_at = $now->toIso8601String();
            }

            return $this->sessions->projection($row, true);
        });
    }
}
