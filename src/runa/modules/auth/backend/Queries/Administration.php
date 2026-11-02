<?php

namespace Runa\Modules\Auth\Queries;

use Carbon\CarbonImmutable;
use Runa\Contracts\Modules\OperationAuthorizer;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\ModuleFramework\Api\OperationScope;
use Runa\Modules\Auth\Actions\SessionStore;
use Runa\Modules\Auth\Contracts\AdministrativeProfile;
use Runa\Modules\Auth\Contracts\AdministrativeUser;
use Runa\Modules\Auth\Contracts\AuthAdministration;
use Runa\Modules\Auth\Contracts\UserDirectoryPage;
use Runa\Modules\Auth\Contracts\UserDirectoryQuery;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Support\AdministrationFeatures;
use Runa\Modules\Auth\Support\PolicyExceptions;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Settings\ModuleSettings;

/** Auth owns every query; no Eloquent model or credential crosses this boundary. */
final readonly class Administration implements AuthAdministration
{
    public function __construct(private OperationAuthorizer $authorizer, private OperationScope $scope, private ModuleSettings $settings) {}

    public function users(UserDirectoryQuery $query): UserDirectoryPage
    {
        if ($query->state !== 'current') {
            $this->require('users.deleted.read');
        }
        $after = null;
        if ($query->after !== null) {
            $decoded = base64_decode($query->after, true);
            $cursor = $decoded === false ? null : json_decode($decoded, true);
            if (! is_array($cursor) || array_keys($cursor) !== ['id', 'query'] || ! is_string($cursor['id'])
                || $cursor['query'] !== hash('sha256', serialize([$query->state, $query->search]))) {
                throw new OperationRejected('directory_cursor_invalid', 'runa.auth', 'administration.users', 422);
            }
            try {
                new TrustedPrincipal($cursor['id']);
            } catch (\InvalidArgumentException) {
                throw new OperationRejected('directory_cursor_invalid', 'runa.auth', 'administration.users', 422);
            }
            $after = $cursor['id'];
        }
        $users = User::query();
        if ($query->state === 'deleted') {
            $users->onlyTrashed();
        } elseif ($query->state === 'all') {
            $users->withTrashed();
        }
        if ($query->search !== null) {
            // Literal substring, including %, _ and backslash. Login uses the same profile read authority.
            $search = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query->search).'%';
            $login = $this->allows('profiles.read');
            $users->where(function ($where) use ($search, $login): void {
                $where->where('name', 'ilike', $search)->orWhere('first_name', 'ilike', $search)->orWhere('surname', 'ilike', $search);
                if ($login) {
                    $where->orWhere('login', 'ilike', $search);
                }
            });
        }
        $total = (clone $users)->count();
        $rows = $users->when($after !== null, fn ($q) => $q->where('public_id', '>', $after))->orderBy('public_id')->limit($query->limit + 1)->get();
        $more = $rows->count() > $query->limit;
        $batch = $rows->take($query->limit)->values()->all();
        $sessions = $this->allows('sessions.manage') ? app(SessionStore::class)->liveRowsForUsers(app('db')->connection(), $batch)
            ->selectRaw('user_id, count(*) AS count, max(authenticated_at) AS authenticated_at, max(activity_at) AS activity_at')
            ->groupBy('user_id')->get()->keyBy('user_id') : collect();
        $permissions = array_fill_keys(['profiles.read', 'emails.read', 'sessions.manage'], false);
        foreach ($permissions as $permission => $_) {
            $permissions[$permission] = $this->allows($permission);
        }
        $items = [];
        foreach ($batch as $user) {
            $items[] = new AdministrativeUser($user->public_id, $user->identity()->displayName, $user->trashed() ? 'deleted' : 'current', $user->version, $this->directoryDetails($user, $sessions->get($user->id), $permissions));
        }
        $next = $more ? base64_encode(json_encode(['id' => $items[array_key_last($items)]->publicId, 'query' => hash('sha256', serialize([$query->state, $query->search]))], JSON_THROW_ON_ERROR)) : null;

        return new UserDirectoryPage($items, $next, $total);
    }

    public function profile(string $publicId): AdministrativeProfile
    {
        $user = $this->user($publicId);
        $verification = $this->settings->value('runa.auth', 'email_verification');
        $primary = $this->allows('emails.read') ? ['email' => $user->email, 'verified' => $user->hasVerifiedEmail(),
            'verification_required' => $user->verification_forced || $verification === 'all' || ($verification === 'new' && $user->verification_required)] : null;
        $secondary = [];
        if ($this->allows('secondary.read')) {
            foreach (app('db')->connection()->table('runa_auth.secondary_emails')->where('user_id', $user->id)->whereNull('deleted_at')->orderBy('public_id')->limit(100)->get(['public_id', 'email', 'verified_at']) as $row) {
                $secondary[] = ['public_id' => $row->public_id, 'email' => $row->email, 'verified' => $row->verified_at !== null];
            }
        }

        return new AdministrativeProfile($publicId, $user->version, $user->name, $user->first_name, $user->surname, $user->login,
            $primary, $secondary, $this->allows('preferences.reset') ? $user->preference_revision : 0);
    }

    public function lifecycle(string $publicId): array
    {
        $user = $this->user($publicId);
        $result = ['public_id' => $user->public_id, 'version' => $user->version, 'deleted' => $user->trashed()];
        foreach (['active', 'approved', 'security_locked', 'password_change_required', 'activate_at', 'deactivate_at', 'expires_at', 'attempt_locked_until'] as $key) {
            $value = $user->$key;
            $result[$key] = $value instanceof \DateTimeInterface ? $value->format('Y-m-d\TH:i:s\Z') : $value;
        }

        $result += $this->directoryDetails($user, $this->allows('sessions.manage')
            ? app(SessionStore::class)->liveRows(app('db')->connection(), $user)
                ->selectRaw('count(*) AS count, max(authenticated_at) AS authenticated_at, max(activity_at) AS activity_at')->first()
            : null);
        if ($this->allows('avatars.manage') && app(AdministrationFeatures::class)->enabled('avatar')) {
            $result['has_avatar'] = $user->avatar_path !== null;
            $result['avatar_max_kib'] = $this->settings->integer('runa.auth', 'avatar_max_kib');
        }
        $result['deleted_at'] = $user->deleted_at?->toIso8601String();
        if ($this->allows('passwords.reset')) {
            $result['password_set'] = $user->password !== null;
            $result['password_changed_at'] = $user->password_changed_at?->toIso8601String();
            if (app(AdministrationFeatures::class)->enabled('password_expiry')) {
                $days = app(PolicyExceptions::class)->value($user, 'password_expiry_days');
                $result['password_expires_at'] = $user->password !== null && $days > 0 ? $user->password_changed_at?->addDays($days)->toIso8601String() : null;
            }
        }
        if ($this->allows('two_factor.reset')) {
            $result['totp_enrolled'] = $user->totp_secret !== null;
            $result['email_factor_enrolled'] = $user->email_factor;
            $result['recovery_codes_remaining'] = count($user->recovery_hashes ?? []);
        }

        return array_filter($result, fn ($key) => app(AdministrationFeatures::class)->field($key), ARRAY_FILTER_USE_KEY);
    }

    /** @param array<string,bool>|null $permissions
     * @return array<string,bool|int|string|null> */
    private function directoryDetails(User $user, ?object $sessions, ?array $permissions = null): array
    {
        $result = ['active' => $user->active, 'approved' => $user->approved,
            'security_locked' => $user->security_locked,
            'activate_at' => $user->activate_at?->toIso8601String(), 'deactivate_at' => $user->deactivate_at?->toIso8601String(),
            'expires_at' => $user->expires_at?->toIso8601String(), 'attempt_locked_until' => $user->attempt_locked_until?->toIso8601String(),
            'created_at' => $user->created_at->toIso8601String(), 'updated_at' => $user->updated_at->toIso8601String()];
        if (($permissions['profiles.read'] ?? $this->allows('profiles.read'))) {
            $result['login'] = $user->login;
        }
        if (($permissions['emails.read'] ?? $this->allows('emails.read'))) {
            $result['email'] = $user->email;
            $result['verified'] = $user->hasVerifiedEmail();
            $result['email_verified_at'] = $user->email_verified_at?->toIso8601String();
        }
        if (($permissions['sessions.manage'] ?? $this->allows('sessions.manage'))) {
            $result['logged_in'] = ! $user->trashed() && (int) ($sessions->count ?? 0) > 0;
            $result['session_count'] = $user->trashed() ? 0 : (int) ($sessions->count ?? 0);
            $result['authenticated_at'] = isset($sessions->authenticated_at) ? CarbonImmutable::parse($sessions->authenticated_at)->utc()->toIso8601String() : null;
            $result['activity_at'] = isset($sessions->activity_at) ? CarbonImmutable::parse($sessions->activity_at)->utc()->toIso8601String() : null;
        }

        return array_filter($result, fn ($key) => app(AdministrationFeatures::class)->field($key), ARRAY_FILTER_USE_KEY);
    }

    private function user(string $publicId): User
    {
        new TrustedPrincipal($publicId);
        $user = User::withTrashed()->where('public_id', $publicId)->first();
        if ($user === null || ($user->trashed() && ! $this->allows('users.deleted.read'))) {
            throw new OperationRejected('identity_missing', 'runa.auth', 'administration.profile', 404);
        }

        return $user;
    }

    private function require(string $permission): void
    {
        if (! $this->allows($permission)) {
            throw new OperationRejected('permission_denied', 'runa.auth', 'administration.users', 403);
        }
    }

    private function allows(string $permission): bool
    {
        $context = $this->scope->current();

        return $context !== null && $context->moduleId === 'runa.auth'
            && $this->authorizer->allows($context, 'runa.auth.'.$permission);
    }
}
