<?php

namespace Runa\Modules\Auth\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\Modules\Auth\Contracts\SecurityMailDelivery;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Queries\LookupIdentity;
use Runa\Modules\Auth\Support\EntryTransaction;
use Runa\Modules\Auth\Support\PolicyExceptions;
use Runa\Support\Modules\Settings\ModuleSettings;
use Runa\Support\Modules\Work\ModuleWork;

/** Reference-only outbox and single-attempt SMTP claims; no generic Notifications runtime. */
final readonly class SecurityNotices
{
    public function __construct(private EntryTransaction $transactions, private ModuleWork $work, private ModuleSettings $settings,
        private DeliveryRouting $routing, private LookupIdentity $identities) {}

    public function issueLocked(Connection $connection, User $user, string $category, string $event, ?CarbonImmutable $deadline = null): void
    {
        if ($connection !== app('db')->connection() || $connection->transactionLevel() < 1) {
            throw new \LogicException('Security notices require their owning transaction.');
        }
        if (! $this->settings->value('runa.auth', 'notice_'.$category)) {
            return;
        }
        $this->routing->category('runa.auth', $category);
        $fingerprint = hash('sha256', $event);
        if ($connection->table('runa_auth.security_notices')->where('user_id', $user->id)->where('category', $category)->where('event', $fingerprint)->exists()) {
            return;
        }
        $frequency = $connection->table('runa_auth.notice_frequencies')->where('user_id', $user->id)->where('category', $category)->first();
        $now = CarbonImmutable::now('UTC');
        if ($frequency !== null && $now->lt(CarbonImmutable::parse($frequency->available_at))) {
            return;
        }
        $id = (string) Str::uuid7();
        $enqueue = $this->work->prepareDispatch('runa.auth', 'security.deliver', ['notice_id' => $id]);
        $connection->table('runa_auth.security_notices')->insert(['public_id' => $id, 'user_id' => $user->id, 'category' => $category,
            'event' => $fingerprint, 'primary_email' => $user->email, 'deadline' => $deadline, 'created_at' => $now,
            'expires_at' => $deadline ?? $now->addDay()]);
        $connection->table('runa_auth.notice_frequencies')->updateOrInsert(['user_id' => $user->id, 'category' => $category],
            ['available_at' => $now->addMinutes($this->settings->value('runa.auth', 'notice_interval_minutes'))]);
        $enqueue();
    }

    public function browserLocked(Connection $connection, User $user, ?string $browser): void
    {
        if ($browser === null || ! preg_match('/\A[0-9a-f]{64}\z/D', $browser)) {
            return;
        }
        $hash = hash_hmac('sha256', $browser, config('app.key'));
        $query = $connection->table('runa_auth.known_browsers')->where('user_id', $user->id)->where('fingerprint', $hash);
        if (! $query->exists()) {
            // A non-credential cookie is only a notice deduplication hint, never trusted-device authority.
            $connection->table('runa_auth.known_browsers')->insert(['user_id' => $user->id, 'fingerprint' => $hash, 'created_at' => now()]);
            $this->issueLocked($connection, $user, 'new_browser', $hash);
            $old = $connection->table('runa_auth.known_browsers')->where('user_id', $user->id)->orderByDesc('id')->offset(100)->limit(100)->pluck('id');
            $connection->table('runa_auth.known_browsers')->whereIn('id', $old)->delete();
        }
    }

    public function reminders(): void
    {
        $this->transactions->run(function (Connection $connection): void {
            $this->transactions->lockAuthority($connection);
            $cursor = $connection->table('runa_auth.notice_scan')->where('id', 1)->lockForUpdate()->value('last_user_id');
            $users = User::where('id', '>', $cursor)->orderBy('id')->limit(100)->lockForUpdate()->get();
            foreach ($users as $user) {
                if (! $this->identities->evaluate($user, CarbonImmutable::now('UTC'))->mayAuthenticate()) {
                    continue;
                }
                foreach (['account_expiry', 'password_expiry'] as $category) {
                    $deadline = $this->deadline($user, $category);
                    if ($deadline === null || ! $deadline->isFuture() || $deadline->gt(CarbonImmutable::now('UTC')->addDays($this->settings->value('runa.auth', 'expiry_start_days')))) {
                        continue;
                    }
                    $rows = $connection->table('runa_auth.security_notices')->where('user_id', $user->id)->where('category', $category)->where('deadline', $deadline);
                    $count = $rows->count();
                    $latest = $rows->max('created_at');
                    if ($count >= $this->settings->value('runa.auth', 'expiry_max_count')
                        || ($latest !== null && CarbonImmutable::parse($latest)->addHours($this->settings->value('runa.auth', 'expiry_interval_hours'))->isFuture())) {
                        continue;
                    }
                    $this->issueLocked($connection, $user, $category, $deadline->toIso8601String().':'.$count, $deadline);
                }
            }
            $connection->table('runa_auth.notice_scan')->where('id', 1)->update(['last_user_id' => $users->count() < 100 ? 0 : $users->last()->id]);
        });
    }

    private function deadline(User $user, string $category): ?CarbonImmutable
    {
        if ($category === 'account_expiry') {
            return $user->expires_at;
        }
        $days = app(PolicyExceptions::class)->value($user, 'password_expiry_days');

        return $days > 0 && $user->password !== null && $user->password_changed_at !== null ? $user->password_changed_at->addDays($days) : null;
    }

    private function valid(User $user, object $notice): bool
    {
        if ($user->trashed() || $notice->primary_email !== $user->email || ! CarbonImmutable::parse($notice->expires_at)->isFuture()
            || ! $this->settings->value('runa.auth', 'notice_'.$notice->category)) {
            return false;
        }
        if ($notice->deadline !== null) {
            $deadline = $this->deadline($user, $notice->category);

            return $deadline !== null && $deadline->equalTo(CarbonImmutable::parse($notice->deadline))
                && $this->identities->evaluate($user, CarbonImmutable::now('UTC'))->mayAuthenticate();
        }

        return true;
    }

    public function deliver(string $publicId): void
    {
        new TrustedPrincipal($publicId);
        $claimed = $this->transactions->run(function (Connection $connection) use ($publicId): bool {
            $this->transactions->lockAuthority($connection);
            [$user, $notice] = $this->locked($connection, $publicId);
            if ($user === null || $notice === null || $notice->claimed_at !== null || ! $this->valid($user, $notice)) {
                return false;
            }
            $connection->table('runa_auth.security_notices')->where('id', $notice->id)->update(['claimed_at' => now()]);

            return true;
        });
        if (! $claimed) {
            return;
        }
        $this->transactions->run(function (Connection $connection) use ($publicId): void {
            $this->transactions->lockAuthority($connection);
            [$user, $notice] = $this->locked($connection, $publicId);
            if ($user === null || $notice === null || $notice->sent_at !== null || ! $this->valid($user, $notice)) {
                return;
            }
            $category = $this->routing->category('runa.auth', $notice->category);
            $recipients = $this->routing->locked($connection, $user, 'runa.auth', $category);
            // Re-resolve a configured admin copy through Auth's current identity predicates.
            $adminId = $notice->category === 'account_expiry' ? $this->settings->value('runa.auth', 'expiry_admin_copy') : null;
            if ($adminId !== null && $recipients !== []) {
                $admin = User::where('public_id', $adminId)->first();
                if ($admin !== null && $admin->email_verified_at !== null && $this->identities->evaluate($admin, CarbonImmutable::now('UTC'))->mayOperate()) {
                    $recipients[] = $admin->email;
                }
            }
            $texts = [
                'new_browser' => ['Logowanie w nowej przeglądarce', 'New browser sign-in'],
                'failed_login' => ['Nieudana próba logowania', 'Failed sign-in attempt'],
                'locked' => ['Logowanie zostało czasowo zablokowane', 'Sign-in temporarily locked'],
                'password_changed' => ['Hasło zostało zmienione', 'Password changed'],
                'account_expiry' => ['Zbliża się termin wygaśnięcia konta', 'Account expiry approaching'],
                'password_expiry' => ['Zbliża się termin wygaśnięcia hasła', 'Password expiry approaching'],
            ];
            $locale = $connection->table('runa_auth.preferences')->where('user_id', $user->id)->where('scope', 'runa:runa.auth:global:locale')->value('value');
            $text = $texts[$notice->category][$locale === '"en"' ? 1 : 0];
            foreach (array_unique($recipients) as $recipient) {
                try {
                    $body = $text.($notice->deadline === null ? '.' : ': '.CarbonImmutable::parse($notice->deadline)->toIso8601String());
                    $contract = SecurityMailDelivery::class;
                    $accepted = app()->bound($contract) && app($contract)->send($recipient, $text, $body);
                    $sent = $accepted ? true : Mail::raw($body, static fn ($message) => $message->to($recipient)->subject($text));
                    if ($sent === null) {
                        throw new \RuntimeException('Mail cancelled.');
                    }
                } catch (\Throwable) {
                    throw new \RuntimeException('Security notice delivery requires operator review.');
                }
            }
            $connection->table('runa_auth.security_notices')->where('id', $notice->id)->update(['sent_at' => now()]);
        });
    }

    private function locked(Connection $connection, string $publicId): array
    {
        $candidate = $connection->table('runa_auth.security_notices')->where('public_id', $publicId)->first();
        $user = $candidate === null ? null : User::withTrashed()->where('id', $candidate->user_id)->lockForUpdate()->first();
        $notice = $candidate === null ? null : $connection->table('runa_auth.security_notices')->where('id', $candidate->id)->lockForUpdate()->first();

        return [$user, $notice];
    }
}
