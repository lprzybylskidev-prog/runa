<?php

namespace Runa\Modules\Auth\Queries;

use Runa\Contracts\Modules\OperationAuthorizer;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\ModuleFramework\Api\OperationScope;
use Runa\Modules\Auth\Contracts\AdministrativeProfile;
use Runa\Modules\Auth\Contracts\AdministrativeUser;
use Runa\Modules\Auth\Contracts\AuthAdministration;
use Runa\Modules\Auth\Contracts\UserDirectoryPage;
use Runa\Modules\Auth\Contracts\UserDirectoryQuery;
use Runa\Modules\Auth\Models\User;
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
        $rows = $users->when($after !== null, fn ($q) => $q->where('public_id', '>', $after))->orderBy('public_id')->limit($query->limit + 1)->get();
        $more = $rows->count() > $query->limit;
        $items = [];
        foreach ($rows->take($query->limit) as $user) {
            $items[] = new AdministrativeUser($user->public_id, $user->identity()->displayName, $user->trashed() ? 'deleted' : 'current', $user->version);
        }
        $next = $more ? base64_encode(json_encode(['id' => $items[array_key_last($items)]->publicId, 'query' => hash('sha256', serialize([$query->state, $query->search]))], JSON_THROW_ON_ERROR)) : null;

        return new UserDirectoryPage($items, $next);
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

        return $result;
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
