<?php

namespace Runa\Modules\Auth\Queries;

use Carbon\CarbonImmutable;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\Modules\Auth\Actions\SessionStore;
use Runa\Modules\Auth\Contracts\AdministrativeUser;
use Runa\Modules\Auth\Contracts\UserDirectoryPage;
use Runa\Modules\Auth\Contracts\UserDirectoryQuery;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Support\AdministrationFeatures;
use Runa\Modules\Auth\Support\DirectoryFields;
use Runa\Modules\Auth\Support\PolicyExceptions;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Settings\ModuleSettings;

/** SQL filtering/counting over the same authorized facts that are rendered. */
final readonly class UserDirectory
{
    public function __construct(private ModuleSettings $settings, private SessionStore $sessions, private AdministrationFeatures $features) {}

    /** @param array<string,bool> $permissions */
    public function read(UserDirectoryQuery $input, array $permissions, ?string $publicId = null): UserDirectoryPage
    {
        $fields = array_filter(DirectoryFields::definitions(), fn ($field) => ($permissions[$field[2]] ?? false) && ($field[3] === null || $this->features->enabled($field[3])));
        $filters = array_filter($input->filters, fn ($value) => $value !== null && $value !== '');
        ksort($filters);
        foreach ($filters as $key => $value) {
            if (! isset($fields[$key]) || in_array($key, ['display_name', 'state'], true)) {
                throw new OperationRejected('directory_filter_unavailable', 'runa.auth', 'administration.users', 422);
            }
            $this->validate($fields[$key][1], $value);
        }
        $signature = hash('sha256', serialize([$input->state, $input->search, $filters]));
        $after = $this->cursor($input->after, $signature);
        $inner = User::query()->select('runa_auth.users.*');
        if ($input->state === 'deleted') {
            $inner->onlyTrashed();
        } elseif ($input->state === 'all') {
            $inner->withTrashed();
        }
        $inner->selectRaw("COALESCE(name, first_name || ' ' || surname) AS display_name, CASE WHEN deleted_at IS NULL THEN 'current' ELSE 'deleted' END AS state");
        $inner->selectRaw('email_verified_at IS NOT NULL AS verified, password IS NOT NULL AS password_set');
        $inner->selectRaw('(verification_forced OR ? OR (? AND verification_required)) AS effective_verification_required, avatar_path IS NOT NULL AS has_avatar', [$this->settings->value('runa.auth', 'email_verification') === 'all', $this->settings->value('runa.auth', 'email_verification') === 'new']);
        if (($permissions['trust.manage'] ?? false) && $this->features->enabled('trust')) {
            $devices = app('db')->connection()->table('runa_auth.trusted_devices')->selectRaw('user_id, security_version, count(*) AS trusted_device_count')->whereNull('revoked_at')->where('expires_at', '>', CarbonImmutable::now('UTC'))->groupBy('user_id', 'security_version');
            $inner->leftJoinSub($devices, 'devices', fn ($join) => $join->on('devices.user_id', '=', 'runa_auth.users.id')->on('devices.security_version', '=', 'runa_auth.users.security_version'))->selectRaw('COALESCE(devices.trusted_device_count,0) AS trusted_device_count');
        }
        $totp = $this->features->enabled('totp') ? 'totp_secret IS NOT NULL' : 'FALSE';
        $email = $this->features->enabled('email_factor') ? '(email_factor AND email_verified_at IS NOT NULL)' : 'FALSE';
        $inner->selectRaw("($totp OR $email) AS two_factor_enabled, (? OR $totp OR $email) AS two_factor_required, totp_secret IS NOT NULL AS totp_enrolled, email_factor AS email_factor_enrolled, COALESCE(jsonb_array_length(recovery_hashes), 0) AS recovery_codes_remaining, (totp_pending IS NOT NULL AND totp_pending_until > CURRENT_TIMESTAMP) AS totp_setup_pending", [$this->settings->value('runa.auth', 'two_factor_required') !== 'optional']);
        if ($permissions['sessions.manage'] ?? false) {
            $retained = app('db')->connection()->table('runa_auth.sessions')->selectRaw('user_id, max(authenticated_at) AS last_login_at, max(activity_at) AS last_activity_at')->groupBy('user_id');
            $inner->leftJoinSub($retained, 'retained_sessions', 'retained_sessions.user_id', '=', 'runa_auth.users.id')->addSelect('retained_sessions.last_login_at', 'retained_sessions.last_activity_at');
            $live = $this->sessions->liveDirectoryRows(app('db')->connection())
                ->selectRaw('runa_auth.sessions.user_id, count(*) AS session_count, max(runa_auth.sessions.authenticated_at) AS authenticated_at, max(runa_auth.sessions.activity_at) AS activity_at')
                ->groupBy('runa_auth.sessions.user_id');
            $inner->leftJoinSub($live, 'live_sessions', 'live_sessions.user_id', '=', 'runa_auth.users.id')
                ->selectRaw('COALESCE(live_sessions.session_count, 0) AS session_count, (deleted_at IS NULL AND COALESCE(live_sessions.session_count, 0) > 0) AS logged_in, live_sessions.authenticated_at, live_sessions.activity_at');
        }
        if ($permissions['invitations.manage'] ?? false) {
            $invitations = app('db')->connection()->table('runa_auth.entry_tokens')->selectRaw('user_id, security_version, max(expires_at) AS invitation_expires_at, max(sent_at) AS invitation_sent_at')
                ->where('purpose', 'invitation')->whereNull('consumed_at')->whereNull('invalidated_at')->where('expires_at', '>', CarbonImmutable::now('UTC'))->groupBy('user_id', 'security_version');
            $inner->leftJoinSub($invitations, 'invitation', fn ($join) => $join->on('invitation.user_id', '=', 'runa_auth.users.id')->on('invitation.security_version', '=', 'runa_auth.users.security_version'))
                ->selectRaw('(invitation.invitation_expires_at IS NOT NULL AND password IS NULL AND deleted_at IS NULL) AS invitation_pending, invitation.invitation_expires_at, invitation.invitation_sent_at');
        }
        if (($permissions['passwords.reset'] ?? false) && $this->features->enabled('password_expiry')) {
            $days = app(PolicyExceptions::class)->queryFor('password_expiry_days', 'runa_auth.users.id');
            $duration = 'COALESCE(('.$days->toSql().'), ?)';
            $bindings = [...$days->getBindings(), $this->settings->integer('runa.auth', 'password_expiry_days')];
            $expiry = "CASE WHEN password IS NOT NULL AND $duration > 0 THEN password_changed_at + $duration * INTERVAL '1 day' ELSE NULL END";
            $inner->selectRaw($expiry.' AS password_expires_at', [...$bindings, ...$bindings]);
            $inner->selectRaw("(password IS NOT NULL AND $duration > 0 AND (password_changed_at IS NULL OR password_changed_at + $duration * INTERVAL '1 day' <= ?)) AS password_expired", [...$bindings, ...$bindings, CarbonImmutable::now('UTC')]);
        }
        $query = User::withoutGlobalScopes()->fromSub($inner->toBase(), 'directory')->select('directory.*');
        if ($input->search !== null) {
            $search = $this->literal($input->search);
            $query->where(function ($where) use ($search, $permissions): void {
                $where->where('display_name', 'ilike', $search)->orWhere('first_name', 'ilike', $search)->orWhere('surname', 'ilike', $search);
                if ($permissions['profiles.read'] ?? false) {
                    $where->orWhere('login', 'ilike', $search);
                }
            });
        }
        foreach ($filters as $key => $value) {
            $type = $fields[$key][1];
            $key = $key === 'verification_required' ? 'effective_verification_required' : $key;
            if ($type === 'text') {
                $query->where($key, 'ilike', $this->literal($value));
            } elseif ($type === 'datetime') {
                $range = json_decode($value, true, flags: JSON_THROW_ON_ERROR);
                if (isset($range['from'])) {
                    $query->where($key, '>=', $range['from']);
                }
                if (isset($range['until'])) {
                    $query->where($key, '<=', $range['until']);
                }
            } else {
                $query->where($key, $value);
            }
        }
        if ($publicId !== null) {
            $query->where('public_id', $publicId);
        }
        $total = (clone $query)->count();
        $rows = $query->when($after !== null, fn ($q) => $q->where('public_id', '>', $after))->orderBy('public_id')->limit($input->limit + 1)->get();
        $items = [];
        foreach ($rows->take($input->limit) as $user) {
            $details = [];
            foreach ($fields as $key => [, $type]) {
                if (in_array($key, ['display_name', 'state'], true)) {
                    continue;
                }
                $value = $user->getAttribute($key === 'verification_required' ? 'effective_verification_required' : $key);
                $details[$key] = $value === null ? null : match ($type) {
                    'datetime' => CarbonImmutable::parse($value)->utc()->toIso8601String(),
                    'integer' => (int) $value, 'boolean' => (bool) $value,
                    default => (string) $value,
                };
            }
            $items[] = new AdministrativeUser($user->public_id, $user->getAttribute('display_name'), $user->getAttribute('state'), $user->version, $details);
        }
        $next = $rows->count() > $input->limit ? base64_encode(json_encode(['id' => $items[array_key_last($items)]->publicId, 'query' => $signature], JSON_THROW_ON_ERROR)) : null;

        return new UserDirectoryPage($items, $next, $total);
    }

    private function cursor(?string $cursor, string $signature): ?string
    {
        if ($cursor === null) {
            return null;
        }
        $decoded = base64_decode($cursor, true);
        $value = $decoded === false ? null : json_decode($decoded, true);
        try {
            if (! is_array($value) || array_keys($value) !== ['id', 'query'] || ! is_string($value['id']) || $value['query'] !== $signature) {
                throw new \InvalidArgumentException;
            }
            new TrustedPrincipal($value['id']);

            return $value['id'];
        } catch (\InvalidArgumentException) {
            throw new OperationRejected('directory_cursor_invalid', 'runa.auth', 'administration.users', 422);
        }
    }

    private function literal(string $value): string
    {
        return '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value).'%';
    }

    private function validate(string $type, mixed $value): void
    {
        $valid = match ($type) {
            'boolean' => is_bool($value), 'integer' => is_int($value) && $value >= 0,
            'text' => is_string($value) && trim($value) === $value && mb_strlen($value) <= 160,
            'datetime' => $this->range($value), default => false,
        };
        if (! $valid) {
            throw new OperationRejected('directory_filter_invalid', 'runa.auth', 'administration.users', 422);
        }
    }

    private function range(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }
        $range = json_decode($value, true);
        if (! is_array($range) || $range === [] || array_diff(array_keys($range), ['from', 'until']) !== []) {
            return false;
        }
        foreach ($range as $instant) {
            if (! is_string($instant) || ! preg_match('/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|\+00:00)\z/D', $instant)
                || ! checkdate((int) substr($instant, 5, 2), (int) substr($instant, 8, 2), (int) substr($instant, 0, 4))
                || (int) substr($instant, 11, 2) > 23 || (int) substr($instant, 14, 2) > 59 || (int) substr($instant, 17, 2) > 59) {
                return false;
            }
        }

        return ! isset($range['from'], $range['until']) || CarbonImmutable::parse($range['from'])->lte(CarbonImmutable::parse($range['until']));
    }
}
