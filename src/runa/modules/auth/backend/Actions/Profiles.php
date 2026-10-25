<?php

namespace Runa\Modules\Auth\Actions;

use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use Runa\Modules\Audit\Contracts\OwnHistory;
use Runa\Modules\Audit\Contracts\OwnHistoryDetails;
use Runa\Modules\Auth\Contracts\Identity;
use Runa\Modules\Auth\Contracts\IdentityConflict;
use Runa\Modules\Auth\Contracts\LifecycleChange;
use Runa\Modules\Auth\Contracts\ProfileChange;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Support\AccountHistory;
use Runa\Modules\Auth\Support\EntryTransaction;
use Runa\Modules\Auth\Support\SecurityContext;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Settings\ModuleSettings;

final readonly class Profiles
{
    public function __construct(private SecurityContext $context, private EntryTransaction $transactions,
        private ModuleSettings $settings, private StepUp $stepUp, private ChangeIdentity $lifecycle, private ModuleApis $apis) {}

    public function show(Request $request): array
    {
        return $this->context->run($request, fn (Connection $connection, User $user): array => $this->projection($user), count: false, unrestricted: true);
    }

    public function edit(Request $request): array
    {
        return $this->context->run($request, function (Connection $connection, User $user) use ($request): array {
            $change = new ProfileChange($request->input('name'), $request->input('first_name'), $request->input('surname'), $request->input('login'));
            $this->editLocked($user, $request->integer('version'), $change, true);

            return $this->projection($user);
        }, count: false, unrestricted: true);
    }

    public function editLocked(User $user, int $version, ProfileChange $change, bool $own): Identity
    {
        $this->version($user, $version);
        $namesChanged = [$user->name, $user->first_name, $user->surname] !== [$change->name, $change->firstName, $change->surname];
        $loginChanged = $user->login !== $change->login;
        if ($own && (($namesChanged && ! $this->value('own_name_editable')) || ($loginChanged && ! $this->value('own_login_editable')))) {
            $this->refuse('profile_field_readonly');
        }
        if ($change->login === null && $this->value('identifier_mode') === 'login') {
            $this->refuse('login_required');
        }
        if ($loginChanged && $change->login !== null && User::withTrashed()->where('login_claim', $change->login)->where('id', '<>', $user->id)->exists()) {
            throw new IdentityConflict('identifier_conflict');
        }
        if ($namesChanged || $loginChanged) {
            $before = $user->only(['name', 'first_name', 'surname', 'login']);
            $user->forceFill(['name' => $change->name, 'first_name' => $change->firstName, 'surname' => $change->surname,
                'login' => $change->login, 'login_claim' => $change->login, 'version' => $user->version + 1]);
            $user->save();
            $changes = [];
            foreach ($before as $field => $old) {
                if ($old !== $user->$field) {
                    $changes[$field] = ['old' => $old, 'new' => $user->$field];
                }
            }
            $this->transactions->record($own ? 'profile.changed' : 'profile.admin_changed', $user, $changes);
        }

        return $user->identity();
    }

    public function selfLifecycle(Request $request): array
    {
        $root = app('db')->connection()->transactionLevel() === 0;
        try {
            return $this->context->run($request, function (Connection $connection, User $user) use ($request): array {
                $operation = $request->string('operation')->toString();
                if (! in_array($operation, ['deactivate', 'delete'], true) || ! $this->value('self_'.$operation)) {
                    $this->refuse('self_change_disabled');
                }
                $this->stepUp->assertVerified('runa.auth.self.'.$operation, 'password', everyAction: true);
                $this->lifecycle->change($user->public_id, $request->integer('version'), new LifecycleChange($operation));

                return ['updated' => true, 'reauthenticate' => true];
            }, count: false, unrestricted: true);
        } catch (IdentityConflict $error) {
            if ($root && $error->getMessage() === 'authority_change_refused') {
                $this->transactions->securityRefused();
            }
            throw $error;
        }
    }

    public function history(Request $request): array
    {
        return $this->context->run($request, function () use ($request): array {
            $kind = $request->string('kind')->toString();
            if (! in_array($kind, ['login', 'account'], true) || ! $this->value('own_'.$kind.'_history')) {
                $this->refuse('own_history_disabled');
            }
            $actions = $kind === 'login' ? ['login.succeeded', 'login.credentials_accepted', 'two_factor.confirmed', 'logout.completed', 'session.evicted', 'session.revoked', 'sessions.revoked'] : array_keys(AccountHistory::fields());

            return $this->apis->resolve('runa.audit', OwnHistory::class, '1.0.0')->page('runa.auth', 'user', $actions, $request->input('before'));
        }, count: false, unrestricted: true);
    }

    public function historyDetail(Request $request): array
    {
        return $this->context->run($request, function () use ($request): array {
            if (! $this->value('own_account_history')) {
                $this->refuse('own_history_disabled');
            }

            return $this->apis->resolve('runa.audit', OwnHistoryDetails::class, '1.0.0')
                ->detail('runa.auth', 'user', $request->string('event')->toString(), AccountHistory::fields())
                ?? throw new OperationRejected('history_event_not_found', 'runa.auth', 'profile.history_detail', 404);
        }, count: false, unrestricted: true);
    }

    public function projection(User $user): array
    {
        $initials = $user->name !== null ? preg_split('/\s+/u', $user->name) : [$user->first_name, $user->surname];
        $initials = mb_strtoupper(mb_substr($initials[0], 0, 1).(count($initials) > 1 ? mb_substr(end($initials), 0, 1) : ''));
        $secondary = $this->value('secondary_emails_enabled') ? app('db')->connection()->table('runa_auth.secondary_emails')
            ->where('user_id', $user->id)->whereNull('deleted_at')->orderBy('id')->get(['public_id', 'email', 'verified_at'])->toArray() : [];
        $capabilities = [];
        foreach (['own_name_editable', 'own_login_editable', 'own_email_editable', 'secondary_emails_enabled', 'secondary_self_add', 'secondary_self_change', 'secondary_self_remove', 'avatar_enabled', 'avatar_editor', 'avatar_max_kib', 'secondary_email_limit', 'own_login_history', 'own_account_history', 'self_deactivate', 'self_delete'] as $key) {
            $capabilities[$key] = $this->value($key);
        }

        return ['public_id' => $user->public_id, 'version' => $user->version, 'display_name' => $user->identity()->displayName,
            'name' => $user->name, 'first_name' => $user->first_name, 'surname' => $user->surname, 'login' => $user->login,
            'email' => $user->email, 'email_verified' => $user->email_verified_at !== null, 'secondary_emails' => $secondary,
            'initials' => $initials, 'has_avatar' => $this->value('avatar_enabled') && $user->avatar_path !== null, 'capabilities' => $capabilities];
    }

    public function version(User $user, int $version): void
    {
        if ($version < 1 || $user->version !== $version) {
            throw new IdentityConflict('identity_stale');
        }
    }

    public function value(string $key): mixed
    {
        return $this->settings->value('runa.auth', $key);
    }

    private function refuse(string $code): never
    {
        throw new OperationRejected($code, 'runa.auth', 'profile', 403);
    }
}
