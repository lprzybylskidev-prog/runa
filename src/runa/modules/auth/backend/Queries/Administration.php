<?php

namespace Runa\Modules\Auth\Queries;

use Runa\Contracts\Modules\OperationAuthorizer;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\ModuleFramework\Api\OperationScope;
use Runa\Modules\Auth\Contracts\AdministrativeProfile;
use Runa\Modules\Auth\Contracts\AuthAdministration;
use Runa\Modules\Auth\Contracts\UserDirectoryPage;
use Runa\Modules\Auth\Contracts\UserDirectoryQuery;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Support\AdministrationFeatures;
use Runa\Modules\Auth\Support\DirectoryFields;
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
        $permissions = [];
        foreach (DirectoryFields::definitions() as [, , $permission]) {
            $permissions[$permission] ??= $this->allows($permission);
        }

        return app(UserDirectory::class)->read($query, $permissions);
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
        return $this->facts($publicId);
    }

    public function facts(string $publicId): array
    {
        $user = $this->user($publicId);
        $permissions = [];
        foreach (DirectoryFields::definitions() as [, , $permission]) {
            $permissions[$permission] ??= $this->allows($permission);
        }
        $facts = app(UserDirectory::class)->read(new UserDirectoryQuery(limit: 1, state: $user->trashed() ? 'deleted' : 'current'), $permissions, $publicId)->items[0]->details;
        $result = ['public_id' => $user->public_id, 'version' => $user->version, 'deleted' => $user->trashed(), ...$facts];
        if ($this->allows('avatars.manage') && app(AdministrationFeatures::class)->enabled('avatar')) {
            $result['has_avatar'] = $user->avatar_path !== null;
            $result['avatar_initials'] = $user->initials();
            $result['avatar_max_kib'] = $this->settings->integer('runa.auth', 'avatar_max_kib');
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
