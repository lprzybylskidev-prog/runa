<?php

namespace Runa\Modules\Auth\Actions;

use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Runa\Modules\Auth\Contracts\IdentityConflict;
use Runa\Modules\Auth\Contracts\NewIdentity;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Support\EntryPassword;
use Runa\Modules\Auth\Support\EntryTransaction;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\Settings\ModuleSettings;

/** No administrative authority or authenticated session is created by registration. */
final readonly class RegisterAccount
{
    public function __construct(private EntryTransaction $transactions, private ModuleSettings $settings, private EntryTokens $tokens) {}

    /** @param array{email: string, password: string, name?: string|null, first_name?: string|null, surname?: string|null, login?: string|null, password_confirmation?: string} $input */
    public function create(#[\SensitiveParameter] array $input): ?User
    {
        $identity = new NewIdentity($input['email'], $input['name'] ?? null, $input['first_name'] ?? null, $input['surname'] ?? null, $input['login'] ?? null);
        Validator::make($input, ['password' => EntryPassword::rules()])->validate();
        $password = Hash::make($input['password']);
        try {
            return $this->transactions->run(function (Connection $connection) use ($identity, $password): ?User {
                if ($this->settings->value('runa.auth', 'public_registration') !== true) {
                    throw new OperationRejected('registration_unavailable', 'runa.auth', 'register', 403);
                }
                if ($this->settings->value('runa.auth', 'identifier_mode') === 'login' && $identity->login === null) {
                    throw new OperationRejected('login_required', 'runa.auth', 'register', 422);
                }
                $approved = $this->settings->value('runa.auth', 'registration_approval') === 'automatic';
                $this->transactions->lockAuthority($connection);
                $user = new User;
                $user->forceFill(['name' => $identity->name, 'first_name' => $identity->firstName, 'surname' => $identity->surname,
                    'email' => $identity->email, 'email_claim' => $identity->email, 'login' => $identity->login, 'login_claim' => $identity->login,
                    'verification_required' => $this->settings->value('runa.auth', 'email_verification') !== 'disabled',
                    'password' => $password, 'password_changed_at' => now(), 'active' => true, 'approved' => $approved, 'version' => 1, 'security_version' => 1]);
                try {
                    app(EmailAddresses::class)->available($connection, $user, $identity->email, true);
                } catch (IdentityConflict) {
                    return null;
                }
                $user->save();
                $this->transactions->record('registration.completed', $user);
                if ($approved && $user->verification_required) {
                    $this->tokens->issueLocked($connection, $user, 'verification');
                }

                return $user;
            });
        } catch (QueryException $error) {
            if ($error->getCode() !== '23505') {
                throw $error;
            }

            // Same accepted response as new registration. Never authenticate a duplicate.
            return null;
        }
    }
}
