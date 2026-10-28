<?php

namespace Runa\Modules\Auth\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Support\EntryTransaction;
use Runa\Modules\Auth\Support\PolicyExceptions;
use Runa\Support\Modules\Settings\ModuleSettings;

/** Private owning store. Call mutations after registry -> authority -> user lock.
 * @phpstan-type SessionRow \stdClass&object{id: int, public_id: string, user_id: int, session_hash: string, security_version: int, remembered: bool, authenticated_at: string, activity_at: string, expires_at: string, revoked_at: string|null, factor_verified_at: string|null, factor_method: string|null}
 */
final readonly class SessionStore
{
    public function __construct(private ModuleSettings $settings, private EntryTransaction $transactions) {}

    public function admit(Connection $connection, User $user, string $sessionId, bool $remember): string
    {
        $now = CarbonImmutable::now('UTC');
        $rememberMinutes = $this->settings->integer('runa.auth', 'session_remember_minutes');
        $remember = $remember && $rememberMinutes > 0;
        $absoluteMinutes = $this->settings->integer('runa.auth', 'session_absolute_minutes');
        $minutes = $remember ? min($rememberMinutes, $absoluteMinutes) : $absoluteMinutes;
        $limit = $this->settings->integer('runa.auth', 'session_limit');
        $active = $this->liveRows($connection, $user)->orderBy('authenticated_at')->orderBy('id')->pluck('id');
        if ($limit > 0 && $active->count() >= $limit) {
            $connection->table('runa_auth.sessions')->whereIn('id', $active->take($active->count() - $limit + 1))->update(['revoked_at' => $now]);
            $this->transactions->record('session.evicted', $user);
        }
        $publicId = (string) Str::uuid7();
        $connection->table('runa_auth.sessions')->insert([
            'public_id' => $publicId, 'user_id' => $user->id, 'session_hash' => hash('sha256', $sessionId),
            'security_version' => $user->security_version, 'remembered' => $remember,
            'authenticated_at' => $now, 'activity_at' => $now, 'expires_at' => $now->addMinutes($minutes),
        ]);

        return $publicId;
    }

    public function liveRows(Connection $connection, User $user): Builder
    {
        $now = CarbonImmutable::now('UTC');

        return $connection->table('runa_auth.sessions')->where('user_id', $user->id)->whereNull('revoked_at')
            ->where('security_version', $user->security_version)->where('expires_at', '>', $now)
            ->where('authenticated_at', '>', $now->subMinutes($this->settings->integer('runa.auth', 'session_absolute_minutes')))
            ->where('activity_at', '>', $now->subMinutes(app(PolicyExceptions::class)->value($user, 'session_idle_minutes')))
            ->where(function (Builder $query) use ($now): void {
                $query->where(fn (Builder $q) => $q->where('remembered', false)->where('authenticated_at', '>', $now->subMinutes($this->settings->integer('runa.auth', 'session_absolute_minutes'))))
                    ->orWhere(fn (Builder $q) => $q->where('remembered', true)->where('authenticated_at', '>', $now->subMinutes($this->settings->integer('runa.auth', 'session_remember_minutes'))));
            });
    }

    /** @return SessionRow|null */
    public function current(Request $request, User $user): ?object
    {
        /** @var SessionRow|null $row */
        $row = app('db')->connection()->table('runa_auth.sessions')->where('public_id', $request->session()->get('auth.session_id'))
            ->where('user_id', $user->id)->where('session_hash', hash('sha256', $request->session()->getId()))->first();
        if ($row === null || $row->revoked_at !== null || $row->security_version !== $user->security_version || ! $this->live($row)) {
            return null;
        }

        return $row;
    }

    /** @param SessionRow $row */
    public function live(object $row): bool
    {
        $user = User::where('id', $row->user_id)->first();
        if ($user === null) {
            return false;
        }
        $now = CarbonImmutable::now('UTC');
        // A stricter policy takes effect immediately; a relaxed policy never moves a captured absolute deadline.
        $policyDeadline = CarbonImmutable::parse($row->authenticated_at)->addMinutes($this->settings->integer('runa.auth',
            $row->remembered ? 'session_remember_minutes' : 'session_absolute_minutes'));
        $absoluteDeadline = CarbonImmutable::parse($row->authenticated_at)->addMinutes($this->settings->integer('runa.auth', 'session_absolute_minutes'));

        return $now->lt(CarbonImmutable::parse($row->expires_at)) && $now->lt($policyDeadline) && $now->lt($absoluteDeadline)
            && $now->lt(CarbonImmutable::parse($row->activity_at)->addMinutes(app(PolicyExceptions::class)->value($user, 'session_idle_minutes')));
    }

    public function revokeUser(Connection $connection, User $user): void
    {
        foreach (['sessions', 'trusted_devices'] as $table) {
            $connection->table('runa_auth.'.$table)->where('user_id', $user->id)->whereNull('revoked_at')
                ->update(['revoked_at' => CarbonImmutable::now('UTC')]);
        }
    }

    /** @param SessionRow $row
     * @return array{public_id: string, current: bool, remembered: bool, authenticated_at: string, activity_at: string, expires_at: string}
     */
    public function projection(object $row, bool $current = false): array
    {
        return ['public_id' => $row->public_id, 'current' => $current, 'remembered' => $row->remembered,
            'authenticated_at' => $row->authenticated_at, 'activity_at' => $row->activity_at, 'expires_at' => $row->expires_at];
    }
}
